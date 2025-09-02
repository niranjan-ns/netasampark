import React from 'react';
import { CalendarIcon } from '@heroicons/react/24/outline';

export default function Calendar() {
  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-gray-900">Calendar & Events</h1>
        <p className="mt-1 text-sm text-gray-500">
          Manage campaign events and schedules
        </p>
      </div>
      
      <div className="card text-center py-12">
        <CalendarIcon className="mx-auto h-12 w-12 text-gray-400" />
        <h3 className="mt-2 text-sm font-medium text-gray-900">Event Calendar</h3>
        <p className="mt-1 text-sm text-gray-500">Campaign event scheduling and management.</p>
        <div className="mt-6">
          <button className="btn-primary">Schedule Event</button>
        </div>
      </div>
    </div>
  );
}