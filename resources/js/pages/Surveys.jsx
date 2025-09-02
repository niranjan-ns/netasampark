import React from 'react';
import { ClipboardDocumentListIcon } from '@heroicons/react/24/outline';

export default function Surveys() {
  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-gray-900">Surveys & Issues</h1>
        <p className="mt-1 text-sm text-gray-500">
          Manage voter surveys and track issues
        </p>
      </div>
      
      <div className="card text-center py-12">
        <ClipboardDocumentListIcon className="mx-auto h-12 w-12 text-gray-400" />
        <h3 className="mt-2 text-sm font-medium text-gray-900">Survey Management</h3>
        <p className="mt-1 text-sm text-gray-500">Voter survey creation and issue tracking system.</p>
        <div className="mt-6">
          <button className="btn-primary">Create Survey</button>
        </div>
      </div>
    </div>
  );
}