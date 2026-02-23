import React from 'react';
import { 
    CheckCircleIcon, 
    ExclamationCircleIcon, 
    ExclamationTriangleIcon,
    InformationCircleIcon 
} from '@heroicons/react/24/outline';

const types = {
    success: {
        icon: CheckCircleIcon,
        bgColor: 'bg-green-50',
        textColor: 'text-green-800',
        iconColor: 'text-green-400',
    },
    error: {
        icon: ExclamationCircleIcon,
        bgColor: 'bg-red-50',
        textColor: 'text-red-800',
        iconColor: 'text-red-400',
    },
    warning: {
        icon: ExclamationTriangleIcon,
        bgColor: 'bg-yellow-50',
        textColor: 'text-yellow-800',
        iconColor: 'text-yellow-400',
    },
    info: {
        icon: InformationCircleIcon,
        bgColor: 'bg-blue-50',
        textColor: 'text-blue-800',
        iconColor: 'text-blue-400',
    },
};

export default function Alert({ type = 'info', title, message, className = '' }) {
    const { icon: Icon, bgColor, textColor, iconColor } = types[type];

    return (
        <div className={`rounded-md p-4 ${bgColor} ${className}`}>
            <div className="flex">
                <div className="flex-shrink-0">
                    <Icon className={`h-5 w-5 ${iconColor}`} />
                </div>
                <div className="ml-3">
                    {title && (
                        <h3 className={`text-sm font-medium ${textColor}`}>{title}</h3>
                    )}
                    {message && (
                        <div className={`text-sm ${textColor} ${title ? 'mt-2' : ''}`}>
                            {message}
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}
