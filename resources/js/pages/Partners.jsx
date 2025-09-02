import React from 'react';
import { UserGroupIcon } from '@heroicons/react/24/outline';

export default function Partners() {
  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-gray-900">Partners & Affiliates</h1>
        <p className="mt-1 text-sm text-gray-500">
          Manage partner relationships and affiliate programs
        </p>
      </div>
      
      <div className="card text-center py-12">
        <UserGroupIcon className="mx-auto h-12 w-12 text-gray-400" />
        <h3 className="mt-2 text-sm font-medium text-gray-900">Partner Management</h3>
        <p className="mt-1 text-sm text-gray-500">Partner and affiliate relationship management system.</p>
        <div className="mt-6">
          <button className="btn-primary">Add Partner</button>
        </div>
      </div>
    </div>
  );
}