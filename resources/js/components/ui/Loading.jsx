import React from 'react';

export default function Loading({ size = 'md', className = '' }) {
    const sizes = {
        sm: 'h-4 w-4',
        md: 'h-8 w-8',
        lg: 'h-12 w-12',
    };

    return (
        <div className={`flex items-center justify-center ${className}`}>
            <div
                className={`animate-spin rounded-full border-b-2 border-green-600 ${sizes[size]}`}
            ></div>
        </div>
    );
}

export function PageLoading() {
    return (
        <div className="min-h-96 flex items-center justify-center">
            <div className="text-center">
                <Loading size="lg" />
                <p className="mt-4 text-gray-500">Memuat data...</p>
            </div>
        </div>
    );
}
