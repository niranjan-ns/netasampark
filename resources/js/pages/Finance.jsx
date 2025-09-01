import React from 'react';
import { BanknotesIcon } from '@heroicons/react/24/outline';

export default function Finance() {
  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-gray-900">Finance & Compliance</h1>
        <p className="mt-1 text-sm text-gray-500">
          Manage campaign finances and compliance
        </p>
      </div>
      
      <div className="card text-center py-12">
        <BanknotesIcon className="mx-auto h-12 w-12 text-gray-400" />
        <h3 className="mt-2 text-sm font-medium text-gray-900">Financial Management</h3>
        <p className="mt-1 text-sm text-gray-500">Campaign expense tracking and compliance management.</p>
        <div className="mt-6">
          <button className="btn-primary">Add Expense</button>
        </div>
      </div>
    </div>
  );
}