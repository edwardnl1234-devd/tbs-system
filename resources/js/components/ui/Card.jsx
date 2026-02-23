import React from 'react';

export function Card({ children, className = '' }) {
    return (
        <div className={`bg-white rounded-lg shadow ${className}`}>
            {children}
        </div>
    );
}

export function CardHeader({ children, className = '' }) {
    return (
        <div className={`px-4 py-5 sm:px-6 border-b border-gray-200 ${className}`}>
            {children}
        </div>
    );
}

export function CardBody({ children, className = '' }) {
    return (
        <div className={`px-4 py-5 sm:p-6 ${className}`}>
            {children}
        </div>
    );
}

export function CardFooter({ children, className = '' }) {
    return (
        <div className={`px-4 py-4 sm:px-6 border-t border-gray-200 bg-gray-50 rounded-b-lg ${className}`}>
            {children}
        </div>
    );
}
