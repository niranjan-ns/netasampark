import React from 'react';
import { TicketIcon } from '@heroicons/react/24/outline';

export default function Ticketing() {
  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-gray-900">Ticketing & Support</h1>
        <p className="mt-1 text-sm text-gray-500">
          Manage support tickets and knowledge base
        </p>
      </div>
      
      <div className="card text-center py-12">
        <TicketIcon className="mx-auto h-12 w-12 text-gray-400" />
        <h3 className="mt-2 text-sm font-medium text-gray-900">Ticketing System</h3>
        <p className="mt-1 text-sm text-gray-500">Support ticket management and knowledge base system.</p>
        <div className="mt-6">
          <button className="btn-primary">Create Ticket</button>
        </div>
      </div>
    </div>
  );
}