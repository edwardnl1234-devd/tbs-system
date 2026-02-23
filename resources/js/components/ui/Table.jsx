import React from 'react';

export default function Table({ children, className = '' }) {
    return (
        <div className="overflow-x-auto">
            <table className={`min-w-full divide-y divide-gray-200 ${className}`}>
                {children}
            </table>
        </div>
    );
}

export function Thead({ children }) {
    return <thead className="bg-gray-50">{children}</thead>;
}

export function Tbody({ children }) {
    return <tbody className="divide-y divide-gray-200 bg-white">{children}</tbody>;
}

export function Tr({ children, className = '' }) {
    return <tr className={className}>{children}</tr>;
}

export function Th({ children, className = '' }) {
    return (
        <th
            scope="col"
            className={`px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider ${className}`}
        >
            {children}
        </th>
    );
}

export function Td({ children, className = '' }) {
    return (
        <td className={`px-6 py-4 whitespace-nowrap text-sm text-gray-900 ${className}`}>
            {children}
        </td>
    );
}
