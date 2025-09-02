import React from 'react';
import { 
  UsersIcon, 
  ChatBubbleLeftRightIcon, 
  TicketIcon, 
  CalendarIcon, 
  ChartBarIcon,
  ExclamationTriangleIcon,
  CheckCircleIcon
} from '@heroicons/react/24/outline';

const stats = [
  { name: 'Total Voters', value: '125,430', change: '+12%', changeType: 'positive', icon: UsersIcon },
  { name: 'Active Campaigns', value: '8', change: '+2', changeType: 'positive', icon: ChatBubbleLeftRightIcon },
  { name: 'Open Tickets', value: '23', change: '-5', changeType: 'negative', icon: TicketIcon },
  { name: 'Upcoming Events', value: '12', change: '+3', changeType: 'positive', icon: CalendarIcon },
];

const recentActivities = [
  { id: 1, type: 'voter_import', message: 'Bulk voter import completed - 5,000 records', time: '2 hours ago', status: 'success' },
  { id: 2, type: 'campaign', message: 'Festival campaign launched successfully', time: '4 hours ago', status: 'success' },
  { id: 3, type: 'ticket', message: 'New support ticket created - #TKT-2024-001', time: '6 hours ago', status: 'pending' },
  { id: 4, type: 'event', message: 'Rally scheduled for next week', time: '1 day ago', status: 'info' },
];

export default function Dashboard() {
  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-gray-900">Dashboard</h1>
        <p className="mt-1 text-sm text-gray-500">
          Welcome to NetaSampark - Your Political Campaign Management Platform
        </p>
      </div>

      {/* Stats */}
      <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        {stats.map((item) => (
          <div key={item.name} className="card">
            <div className="flex items-center">
              <div className="flex-shrink-0">
                <item.icon className="h-8 w-8 text-primary-600" />
              </div>
              <div className="ml-4">
                <p className="text-sm font-medium text-gray-500">{item.name}</p>
                <p className="text-2xl font-semibold text-gray-900">{item.value}</p>
                <p className={`text-sm ${
                  item.changeType === 'positive' ? 'text-green-600' : 'text-red-600'
                }`}>
                  {item.change}
                </p>
              </div>
            </div>
          </div>
        ))}
      </div>

      {/* Quick Actions */}
      <div className="grid grid-cols-1 gap-5 lg:grid-cols-2">
        <div className="card">
          <h3 className="text-lg font-medium text-gray-900 mb-4">Quick Actions</h3>
          <div className="grid grid-cols-2 gap-3">
            <button className="btn-primary">Import Voters</button>
            <button className="btn-secondary">Create Campaign</button>
            <button className="btn-secondary">Schedule Event</button>
            <button className="btn-secondary">View Reports</button>
          </div>
        </div>

        <div className="card">
          <h3 className="text-lg font-medium text-gray-900 mb-4">System Health</h3>
          <div className="space-y-3">
            <div className="flex items-center justify-between">
              <span className="text-sm text-gray-600">Database</span>
              <CheckCircleIcon className="h-5 w-5 text-green-500" />
            </div>
            <div className="flex items-center justify-between">
              <span className="text-sm text-gray-600">API Services</span>
              <CheckCircleIcon className="h-5 w-5 text-green-500" />
            </div>
            <div className="flex items-center justify-between">
              <span className="text-sm text-gray-600">Storage</span>
              <ExclamationTriangleIcon className="h-5 w-5 text-yellow-500" />
            </div>
            <div className="flex items-center justify-between">
              <span className="text-sm text-gray-600">Backup</span>
              <CheckCircleIcon className="h-5 w-5 text-green-500" />
            </div>
          </div>
        </div>
      </div>

      {/* Recent Activities */}
      <div className="card">
        <h3 className="text-lg font-medium text-gray-900 mb-4">Recent Activities</h3>
        <div className="space-y-3">
          {recentActivities.map((activity) => (
            <div key={activity.id} className="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg">
              <div className={`w-2 h-2 rounded-full ${
                activity.status === 'success' ? 'bg-green-500' :
                activity.status === 'pending' ? 'bg-yellow-500' : 'bg-blue-500'
              }`} />
              <div className="flex-1">
                <p className="text-sm text-gray-900">{activity.message}</p>
                <p className="text-xs text-gray-500">{activity.time}</p>
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
}