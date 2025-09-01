import React from 'react';
import { BrowserRouter as Router, Routes, Route } from 'react-router-dom';
import Layout from './components/Layout';
import Dashboard from './pages/Dashboard';
import VoterCRM from './pages/VoterCRM';
import CommunicationHub from './pages/CommunicationHub';
import Ticketing from './pages/Ticketing';
import Calendar from './pages/Calendar';
import NewsEvents from './pages/NewsEvents';
import Surveys from './pages/Surveys';
import Analytics from './pages/Analytics';
import Finance from './pages/Finance';
import Partners from './pages/Partners';

function App() {
  return (
    <Router>
      <Layout>
        <Routes>
          <Route path="/" element={<Dashboard />} />
          <Route path="/voter-crm" element={<VoterCRM />} />
          <Route path="/communication" element={<CommunicationHub />} />
          <Route path="/ticketing" element={<Ticketing />} />
          <Route path="/calendar" element={<Calendar />} />
          <Route path="/news-events" element={<NewsEvents />} />
          <Route path="/surveys" element={<Surveys />} />
          <Route path="/analytics" element={<Analytics />} />
          <Route path="/finance" element={<Finance />} />
          <Route path="/partners" element={<Partners />} />
        </Routes>
      </Layout>
    </Router>
  );
}

export default App;