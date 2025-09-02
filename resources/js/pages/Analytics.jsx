import React from 'react';
import { ChartBarIcon } from '@heroicons/react/24/outline';

export default function Analytics() {
  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-gray-900">Analytics & Predictions</h1>
        <p className="mt-1 text-sm text-gray-500">
          Campaign analytics and voter prediction models
        </p>
      </div>
      
      <div className="card text-center py-12">
        <ChartBarIcon className="mx-auto h-12 w-12 text-gray-400" />
        <h3 className="mt-2 text-sm font-medium text-gray-900">Analytics Dashboard</h3>
        <p className="mt-1 text-sm text-gray-500">Campaign performance analytics and voter prediction models.</p>
        <div className="mt-6">
          <button className="btn-primary">View Reports</button>
        </div>
      </div>
    </div>
  );
}