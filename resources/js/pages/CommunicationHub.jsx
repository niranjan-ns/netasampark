import React, { useState } from 'react';
import { 
  PlusIcon, 
  ChatBubbleLeftRightIcon,
  PhoneIcon,
  EnvelopeIcon,
  PaperAirplaneIcon,
  ClockIcon,
  CheckCircleIcon,
  ExclamationTriangleIcon,
  ChartBarIcon
} from '@heroicons/react/24/outline';

const mockCampaigns = [
  {
    id: 1,
    name: 'Festival Greetings Campaign',
    type: 'WhatsApp',
    status: 'Active',
    recipients: 5000,
    sent: 4800,
    delivered: 4650,
    opened: 3200,
    replies: 150,
    scheduledFor: null
  },
  {
    id: 2,
    name: 'Election Update SMS',
    type: 'SMS',
    status: 'Scheduled',
    recipients: 10000,
    sent: 0,
    delivered: 0,
    opened: 0,
    replies: 0,
    scheduledFor: '2024-09-15 10:00 AM'
  },
  {
    id: 3,
    name: 'Voice Call Reminder',
    type: 'Voice',
    status: 'Completed',
    recipients: 2000,
    sent: 2000,
    delivered: 1850,
    opened: 0,
    replies: 0,
    scheduledFor: null
  }
];

const mockInbox = [
  {
    id: 1,
    from: '+91 98765 43210',
    message: 'Thank you for the festival wishes!',
    type: 'WhatsApp',
    time: '2 hours ago',
    status: 'unread'
  },
  {
    id: 2,
    from: '+91 87654 32109',
    message: 'When is the next rally?',
    type: 'SMS',
    time: '4 hours ago',
    status: 'read'
  },
  {
    id: 3,
    from: '+91 76543 21098',
    message: 'I want to volunteer for the campaign',
    type: 'WhatsApp',
    time: '1 day ago',
    status: 'read'
  }
];

export default function CommunicationHub() {
  const [activeTab, setActiveTab] = useState('campaigns');

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Communication Hub</h1>
          <p className="mt-1 text-sm text-gray-500">
            Manage SMS, WhatsApp, Email, and Voice campaigns
          </p>
        </div>
        <button className="btn-primary flex items-center">
          <PlusIcon className="h-5 w-5 mr-2" />
          New Campaign
        </button>
      </div>

      {/* Stats */}
      <div className="grid grid-cols-1 sm:grid-cols-4 gap-5">
        <div className="card text-center">
          <div className="text-2xl font-bold text-blue-600">15</div>
          <div className="text-sm text-gray-500">Active Campaigns</div>
        </div>
        <div className="card text-center">
          <div className="text-2xl font-bold text-green-600">45,230</div>
          <div className="text-sm text-gray-500">Messages Sent</div>
        </div>
        <div className="card text-center">
          <div className="text-2xl font-bold text-yellow-600">2,450</div>
          <div className="text-sm text-gray-500">Replies Received</div>
        </div>
        <div className="card text-center">
          <div className="text-2xl font-bold text-purple-600">98.5%</div>
          <div className="text-sm text-gray-500">Delivery Rate</div>
        </div>
      </div>

      {/* Tabs */}
      <div className="card">
        <div className="border-b border-gray-200">
          <nav className="-mb-px flex space-x-8">
            {[
              { id: 'campaigns', name: 'Campaigns', icon: ChatBubbleLeftRightIcon },
              { id: 'inbox', name: 'Inbox', icon: EnvelopeIcon },
              { id: 'templates', name: 'Templates', icon: PaperAirplaneIcon },
              { id: 'analytics', name: 'Analytics', icon: ChartBarIcon }
            ].map((tab) => (
              <button
                key={tab.id}
                onClick={() => setActiveTab(tab.id)}
                className={`py-2 px-1 border-b-2 font-medium text-sm flex items-center ${
                  activeTab === tab.id
                    ? 'border-primary-500 text-primary-600'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                }`}
              >
                <tab.icon className="h-5 w-5 mr-2" />
                {tab.name}
              </button>
            ))}
          </nav>
        </div>

        <div className="mt-6">
          {activeTab === 'campaigns' && (
            <div className="space-y-4">
              <div className="flex justify-between items-center">
                <h3 className="text-lg font-medium text-gray-900">Campaigns</h3>
                <div className="flex space-x-2">
                  <select className="form-input">
                    <option>All Types</option>
                    <option>WhatsApp</option>
                    <option>SMS</option>
                    <option>Email</option>
                    <option>Voice</option>
                  </select>
                  <select className="form-input">
                    <option>All Status</option>
                    <option>Active</option>
                    <option>Scheduled</option>
                    <option>Completed</option>
                    <option>Paused</option>
                  </select>
                </div>
              </div>

              <div className="overflow-x-auto">
                <table className="min-w-full divide-y divide-gray-200">
                  <thead className="bg-gray-50">
                    <tr>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Campaign</th>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Progress</th>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Engagement</th>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                  </thead>
                  <tbody className="bg-white divide-y divide-gray-200">
                    {mockCampaigns.map((campaign) => (
                      <tr key={campaign.id} className="hover:bg-gray-50">
                        <td className="px-6 py-4 whitespace-nowrap">
                          <div>
                            <div className="text-sm font-medium text-gray-900">{campaign.name}</div>
                            {campaign.scheduledFor && (
                              <div className="text-sm text-gray-500">
                                <ClockIcon className="h-4 w-4 inline mr-1" />
                                {campaign.scheduledFor}
                              </div>
                            )}
                          </div>
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap">
                          <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
                            campaign.type === 'WhatsApp' ? 'bg-green-100 text-green-800' :
                            campaign.type === 'SMS' ? 'bg-blue-100 text-blue-800' :
                            campaign.type === 'Voice' ? 'bg-purple-100 text-purple-800' :
                            'bg-gray-100 text-gray-800'
                          }`}>
                            {campaign.type === 'WhatsApp' && <ChatBubbleLeftRightIcon className="h-4 w-4 mr-1" />}
                            {campaign.type === 'SMS' && <PhoneIcon className="h-4 w-4 mr-1" />}
                            {campaign.type === 'Voice' && <PhoneIcon className="h-4 w-4 mr-1" />}
                            {campaign.type}
                          </span>
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap">
                          <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
                            campaign.status === 'Active' ? 'bg-green-100 text-green-800' :
                            campaign.status === 'Scheduled' ? 'bg-yellow-100 text-yellow-800' :
                            campaign.status === 'Completed' ? 'bg-blue-100 text-blue-800' :
                            'bg-gray-100 text-gray-800'
                          }`}>
                            {campaign.status === 'Active' && <CheckCircleIcon className="h-4 w-4 mr-1" />}
                            {campaign.status === 'Scheduled' && <ClockIcon className="h-4 w-4 mr-1" />}
                            {campaign.status === 'Completed' && <CheckCircleIcon className="h-4 w-4 mr-1" />}
                            {campaign.status}
                          </span>
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap">
                          <div className="text-sm text-gray-900">
                            {campaign.sent.toLocaleString()} / {campaign.recipients.toLocaleString()}
                          </div>
                          <div className="w-full bg-gray-200 rounded-full h-2 mt-1">
                            <div 
                              className="bg-primary-600 h-2 rounded-full" 
                              style={{ width: `${(campaign.sent / campaign.recipients) * 100}%` }}
                            ></div>
                          </div>
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap">
                          <div className="text-sm text-gray-900">
                            <div>Delivered: {campaign.delivered.toLocaleString()}</div>
                            <div>Opened: {campaign.opened.toLocaleString()}</div>
                            <div>Replies: {campaign.replies.toLocaleString()}</div>
                          </div>
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                          <button className="text-primary-600 hover:text-primary-900 mr-3">View</button>
                          <button className="text-yellow-600 hover:text-yellow-900 mr-3">Edit</button>
                          <button className="text-red-600 hover:text-red-900">Stop</button>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>
          )}

          {activeTab === 'inbox' && (
            <div className="space-y-4">
              <h3 className="text-lg font-medium text-gray-900">Inbox Messages</h3>
              <div className="space-y-3">
                {mockInbox.map((message) => (
                  <div key={message.id} className={`p-4 border rounded-lg ${
                    message.status === 'unread' ? 'bg-blue-50 border-blue-200' : 'bg-white border-gray-200'
                  }`}>
                    <div className="flex justify-between items-start">
                      <div className="flex-1">
                        <div className="flex items-center space-x-2">
                          <span className="text-sm font-medium text-gray-900">{message.from}</span>
                          <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
                            message.type === 'WhatsApp' ? 'bg-green-100 text-green-800' :
                            'bg-blue-100 text-blue-800'
                          }`}>
                            {message.type}
                          </span>
                        </div>
                        <p className="text-sm text-gray-700 mt-1">{message.message}</p>
                      </div>
                      <div className="text-xs text-gray-500">{message.time}</div>
                    </div>
                    <div className="mt-3 flex space-x-2">
                      <button className="text-sm text-primary-600 hover:text-primary-900">Reply</button>
                      <button className="text-sm text-gray-600 hover:text-gray-900">Forward</button>
                      <button className="text-sm text-gray-600 hover:text-gray-900">Mark as Read</button>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          )}

          {activeTab === 'templates' && (
            <div className="text-center py-12">
              <PaperAirplaneIcon className="mx-auto h-12 w-12 text-gray-400" />
              <h3 className="mt-2 text-sm font-medium text-gray-900">Templates</h3>
              <p className="mt-1 text-sm text-gray-500">Manage your message templates here.</p>
              <div className="mt-6">
                <button className="btn-primary">Create Template</button>
              </div>
            </div>
          )}

          {activeTab === 'analytics' && (
            <div className="text-center py-12">
              <ChartBarIcon className="mx-auto h-12 w-12 text-gray-400" />
              <h3 className="mt-2 text-sm font-medium text-gray-900">Analytics</h3>
              <p className="mt-1 text-sm text-gray-500">View detailed campaign analytics and reports.</p>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}