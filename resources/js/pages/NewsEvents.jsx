import React from 'react';
import { NewspaperIcon } from '@heroicons/react/24/outline';

export default function NewsEvents() {
  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-gray-900">News & Events</h1>
        <p className="mt-1 text-sm text-gray-500">
          Monitor local news and political events
        </p>
      </div>
      
      <div className="card text-center py-12">
        <NewspaperIcon className="mx-auto h-12 w-12 text-gray-400" />
        <h3 className="mt-2 text-sm font-medium text-gray-900">News Monitoring</h3>
        <p className="mt-1 text-sm text-gray-500">Local news tracking and political event monitoring.</p>
        <div className="mt-6">
          <button className="btn-primary">View News</button>
        </div>
      </div>
    </div>
  );
}