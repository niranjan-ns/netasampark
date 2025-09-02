import React, { useState } from 'react';
import { 
  PlusIcon, 
  MagnifyingGlassIcon, 
  FunnelIcon,
  MapPinIcon,
  PhoneIcon,
  EnvelopeIcon
} from '@heroicons/react/24/outline';

const mockVoters = [
  {
    id: 1,
    name: 'Rajesh Kumar',
    phone: '+91 98765 43210',
    email: 'rajesh.kumar@email.com',
    constituency: 'Mumbai Central',
    booth: 'B-15',
    age: 35,
    gender: 'Male',
    status: 'Active'
  },
  {
    id: 2,
    name: 'Priya Sharma',
    phone: '+91 87654 32109',
    email: 'priya.sharma@email.com',
    constituency: 'Mumbai Central',
    booth: 'B-12',
    age: 28,
    gender: 'Female',
    status: 'Active'
  },
  {
    id: 3,
    name: 'Amit Patel',
    phone: '+91 76543 21098',
    email: 'amit.patel@email.com',
    constituency: 'Mumbai Central',
    booth: 'B-18',
    age: 42,
    gender: 'Male',
    status: 'Inactive'
  }
];

export default function VoterCRM() {
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedConstituency, setSelectedConstituency] = useState('all');

  const filteredVoters = mockVoters.filter(voter => 
    voter.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
    voter.phone.includes(searchTerm) ||
    voter.email.toLowerCase().includes(searchTerm.toLowerCase())
  );

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Voter CRM</h1>
          <p className="mt-1 text-sm text-gray-500">
            Manage voter database and constituency information
          </p>
        </div>
        <button className="btn-primary flex items-center">
          <PlusIcon className="h-5 w-5 mr-2" />
          Add Voter
        </button>
      </div>

      {/* Filters and Search */}
      <div className="card">
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div className="relative">
            <MagnifyingGlassIcon className="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400" />
            <input
              type="text"
              placeholder="Search voters..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              className="form-input pl-10"
            />
          </div>
          <select
            value={selectedConstituency}
            onChange={(e) => setSelectedConstituency(e.target.value)}
            className="form-input"
          >
            <option value="all">All Constituencies</option>
            <option value="mumbai-central">Mumbai Central</option>
            <option value="mumbai-north">Mumbai North</option>
            <option value="mumbai-south">Mumbai South</option>
          </select>
          <button className="btn-secondary flex items-center justify-center">
            <FunnelIcon className="h-5 w-5 mr-2" />
            More Filters
          </button>
        </div>
      </div>

      {/* Stats */}
      <div className="grid grid-cols-1 sm:grid-cols-4 gap-5">
        <div className="card text-center">
          <div className="text-2xl font-bold text-primary-600">125,430</div>
          <div className="text-sm text-gray-500">Total Voters</div>
        </div>
        <div className="card text-center">
          <div className="text-2xl font-bold text-green-600">98,245</div>
          <div className="text-sm text-gray-500">Active Voters</div>
        </div>
        <div className="card text-center">
          <div className="text-2xl font-bold text-blue-600">45</div>
          <div className="text-sm text-gray-500">Constituencies</div>
        </div>
        <div className="card text-center">
          <div className="text-2xl font-bold text-purple-600">1,250</div>
          <div className="text-sm text-gray-500">Booths</div>
        </div>
      </div>

      {/* Voters Table */}
      <div className="card">
        <div className="flex justify-between items-center mb-4">
          <h3 className="text-lg font-medium text-gray-900">Voter List</h3>
          <span className="text-sm text-gray-500">{filteredVoters.length} voters found</span>
        </div>
        
        <div className="overflow-x-auto">
          <table className="min-w-full divide-y divide-gray-200">
            <thead className="bg-gray-50">
              <tr>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Voter</th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Details</th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody className="bg-white divide-y divide-gray-200">
              {filteredVoters.map((voter) => (
                <tr key={voter.id} className="hover:bg-gray-50">
                  <td className="px-6 py-4 whitespace-nowrap">
                    <div>
                      <div className="text-sm font-medium text-gray-900">{voter.name}</div>
                      <div className="text-sm text-gray-500">ID: {voter.id}</div>
                    </div>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap">
                    <div className="space-y-1">
                      <div className="flex items-center text-sm text-gray-900">
                        <PhoneIcon className="h-4 w-4 mr-2" />
                        {voter.phone}
                      </div>
                      <div className="flex items-center text-sm text-gray-500">
                        <EnvelopeIcon className="h-4 w-4 mr-2" />
                        {voter.email}
                      </div>
                    </div>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap">
                    <div className="space-y-1">
                      <div className="flex items-center text-sm text-gray-900">
                        <MapPinIcon className="h-4 w-4 mr-2" />
                        {voter.constituency}
                      </div>
                      <div className="text-sm text-gray-500">Booth: {voter.booth}</div>
                    </div>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap">
                    <div className="text-sm text-gray-900">
                      <div>Age: {voter.age}</div>
                      <div>Gender: {voter.gender}</div>
                    </div>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap">
                    <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${
                      voter.status === 'Active' 
                        ? 'bg-green-100 text-green-800' 
                        : 'bg-red-100 text-red-800'
                    }`}>
                      {voter.status}
                    </span>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <button className="text-primary-600 hover:text-primary-900 mr-3">Edit</button>
                    <button className="text-red-600 hover:text-red-900">Delete</button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}