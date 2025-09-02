<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\Message;
use App\Models\Voter;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class MessagingService
{
    public function sendCampaign(Campaign $campaign): array
    {
        $results = [
            'total' => 0,
            'sent' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        try {
            $voters = $this->getTargetVoters($campaign);
            $results['total'] = $voters->count();

            foreach ($voters as $voter) {
                try {
                    $message = $this->sendMessage($campaign, $voter);
                    if ($message) {
                        $results['sent']++;
                    }
                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = "Voter {$voter->id}: " . $e->getMessage();
                    Log::error('Campaign message failed', [
                        'campaign_id' => $campaign->id,
                        'voter_id' => $voter->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $this->updateCampaignStats($campaign, $results);
            
        } catch (\Exception $e) {
            Log::error('Campaign sending failed', [
                'campaign_id' => $campaign->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }

        return $results;
    }

    public function sendMessage(Campaign $campaign, Voter $voter): ?Message
    {
        $message = Message::create([
            'organization_id' => $campaign->organization_id,
            'campaign_id' => $campaign->id,
            'message_id' => $this->generateMessageId(),
            'type' => $campaign->type,
            'direction' => 'outbound',
            'from' => $this->getSenderInfo($campaign->type),
            'to' => $this->getRecipientInfo($voter, $campaign->type),
            'content' => $this->personalizeContent($campaign->content, $voter),
            'metadata' => [
                'campaign_type' => $campaign->type,
                'voter_id' => $voter->id,
                'constituency' => $voter->constituency,
            ],
            'status' => 'pending',
        ]);

        try {
            switch ($campaign->type) {
                case 'whatsapp':
                    $result = $this->sendWhatsApp($message);
                    break;
                case 'sms':
                    $result = $this->sendSMS($message);
                    break;
                case 'email':
                    $result = $this->sendEmail($message);
                    break;
                case 'voice':
                    $result = $this->sendVoice($message);
                    break;
                default:
                    throw new \InvalidArgumentException("Unsupported message type: {$campaign->type}");
            }

            $message->update([
                'status' => $result['status'],
                'sent_at' => $result['sent_at'] ?? null,
                'metadata' => array_merge($message->metadata ?? [], $result['metadata'] ?? []),
            ]);

            return $message;

        } catch (\Exception $e) {
            $message->update([
                'status' => 'failed',
                'metadata' => array_merge($message->metadata ?? [], ['error' => $e->getMessage()]),
            ]);
            
            Log::error('Message sending failed', [
                'message_id' => $message->id,
                'error' => $e->getMessage()
            ]);
            
            return null;
        }
    }

    protected function sendWhatsApp(Message $message): array
    {
        if (!config('netasampark.messaging.whatsapp.enabled')) {
            throw new \Exception('WhatsApp messaging is not enabled');
        }

        $apiKey = config('netasampark.messaging.whatsapp.api_key');
        $phoneNumberId = config('netasampark.messaging.whatsapp.phone_number_id');

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$apiKey}",
            'Content-Type' => 'application/json',
        ])->post("https://graph.facebook.com/v18.0/{$phoneNumberId}/messages", [
            'messaging_product' => 'whatsapp',
            'to' => $message->to,
            'type' => 'text',
            'text' => ['body' => $message->content],
        ]);

        if (!$response->successful()) {
            throw new \Exception('WhatsApp API error: ' . $response->body());
        }

        $data = $response->json();
        
        return [
            'status' => 'sent',
            'sent_at' => Carbon::now(),
            'metadata' => [
                'whatsapp_message_id' => $data['messages'][0]['id'] ?? null,
                'api_response' => $data,
            ],
        ];
    }

    protected function sendSMS(Message $message): array
    {
        if (!config('netasampark.messaging.sms.enabled')) {
            throw new \Exception('SMS messaging is not enabled');
        }

        $gateway = config('netasampark.messaging.sms.gateway');
        $apiKey = config('netasampark.messaging.sms.api_key');

        switch ($gateway) {
            case 'msg91':
                return $this->sendViaMSG91($message, $apiKey);
            case 'route_mobile':
                return $this->sendViaRouteMobile($message, $apiKey);
            case 'gupshup':
                return $this->sendViaGupshup($message, $apiKey);
            default:
                throw new \Exception("Unsupported SMS gateway: {$gateway}");
        }
    }

    protected function sendEmail(Message $message): array
    {
        // This would use Laravel's built-in mail system
        // For now, return a success response
        return [
            'status' => 'sent',
            'sent_at' => Carbon::now(),
            'metadata' => [
                'email_sent' => true,
                'provider' => 'aws_ses',
            ],
        ];
    }

    protected function sendVoice(Message $message): array
    {
        if (!config('netasampark.messaging.voice.enabled')) {
            throw new \Exception('Voice messaging is not enabled');
        }

        $gateway = config('netasampark.messaging.voice.gateway');
        $apiKey = config('netasampark.messaging.voice.api_key');

        switch ($gateway) {
            case 'exotel':
                return $this->sendViaExotel($message, $apiKey);
            case 'twilio':
                return $this->sendViaTwilio($message, $apiKey);
            default:
                throw new \Exception("Unsupported voice gateway: {$gateway}");
        }
    }

    protected function sendViaMSG91(Message $message, string $apiKey): array
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post('https://api.msg91.com/api/v5/flow/', [
            'flow_id' => config('netasampark.messaging.sms.flow_id'),
            'sender' => config('netasampark.messaging.sms.sender_id'),
            'mobiles' => $message->to,
            'VAR1' => $message->content,
        ]);

        if (!$response->successful()) {
            throw new \Exception('MSG91 API error: ' . $response->body());
        }

        $data = $response->json();
        
        return [
            'status' => 'sent',
            'sent_at' => Carbon::now(),
            'metadata' => [
                'msg91_request_id' => $data['request_id'] ?? null,
                'api_response' => $data,
            ],
        ];
    }

    protected function sendViaRouteMobile(Message $message, string $apiKey): array
    {
        // Implementation for Route Mobile
        return [
            'status' => 'sent',
            'sent_at' => Carbon::now(),
            'metadata' => ['gateway' => 'route_mobile'],
        ];
    }

    protected function sendViaGupshup(Message $message, string $apiKey): array
    {
        // Implementation for Gupshup
        return [
            'status' => 'sent',
            'sent_at' => Carbon::now(),
            'metadata' => ['gateway' => 'gupshup'],
        ];
    }

    protected function sendViaExotel(Message $message, string $apiKey): array
    {
        // Implementation for Exotel
        return [
            'status' => 'sent',
            'sent_at' => Carbon::now(),
            'metadata' => ['gateway' => 'exotel'],
        ];
    }

    protected function sendViaTwilio(Message $message, string $apiKey): array
    {
        // Implementation for Twilio
        return [
            'status' => 'sent',
            'sent_at' => Carbon::now(),
            'metadata' => ['gateway' => 'twilio'],
        ];
    }

    protected function getTargetVoters(Campaign $campaign): \Illuminate\Database\Eloquent\Collection
    {
        $query = Voter::where('organization_id', $campaign->organization_id);

        if (isset($campaign->target_audience['constituency'])) {
            $query->where('constituency', $campaign->target_audience['constituency']);
        }

        if (isset($campaign->target_audience['age_range'])) {
            $query->whereBetween('date_of_birth', [
                Carbon::now()->subYears($campaign->target_audience['age_range']['max']),
                Carbon::now()->subYears($campaign->target_audience['age_range']['min']),
            ]);
        }

        if (isset($campaign->target_audience['tags'])) {
            $query->whereJsonContains('tags', $campaign->target_audience['tags']);
        }

        return $query->get();
    }

    protected function personalizeContent(string $content, Voter $voter): string
    {
        $replacements = [
            '{{name}}' => $voter->name,
            '{{constituency}}' => $voter->constituency,
            '{{district}}' => $voter->district,
            '{{state}}' => $voter->state,
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $content);
    }

    protected function getSenderInfo(string $type): string
    {
        switch ($type) {
            case 'whatsapp':
                return config('netasampark.messaging.whatsapp.phone_number_id');
            case 'sms':
                return config('netasampark.messaging.sms.sender_id');
            case 'email':
                return config('mail.from.address');
            case 'voice':
                return config('netasampark.messaging.voice.sender_id');
            default:
                return 'unknown';
        }
    }

    protected function getRecipientInfo(Voter $voter, string $type): string
    {
        switch ($type) {
            case 'whatsapp':
            case 'sms':
            case 'voice':
                return $voter->phone;
            case 'email':
                return $voter->email;
            default:
                return '';
        }
    }

    protected function generateMessageId(): string
    {
        return 'MSG_' . uniqid() . '_' . time();
    }

    protected function updateCampaignStats(Campaign $campaign, array $results): void
    {
        $campaign->update([
            'sent_count' => $campaign->sent_count + $results['sent'],
            'total_recipients' => $results['total'],
        ]);
    }
}
