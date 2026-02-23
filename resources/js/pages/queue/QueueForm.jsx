import React from 'react';
import { Navigate } from 'react-router-dom';

// QueueForm redirects to QueueList since we use modal for create
export default function QueueForm() {
    return <Navigate to="/queues" replace />;
}
