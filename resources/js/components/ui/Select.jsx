import React from 'react';

export default function Select({
    label,
    options = [],
    error,
    placeholder = 'Pilih...',
    className = '',
    ...props
}) {
    return (
        <div className={className}>
            {label && (
                <label className="block text-sm font-medium text-gray-700 mb-1">
                    {label}
                </label>
            )}
            <select
                className={`
                    block w-full rounded-md shadow-sm
                    focus:ring-green-500 focus:border-green-500
                    ${error
                        ? 'border-red-300 text-red-900'
                        : 'border-gray-300'
                    }
                    sm:text-sm
                `}
                {...props}
            >
                <option value="">{placeholder}</option>
                {options.map((option) => (
                    <option key={option.value} value={option.value}>
                        {option.label}
                    </option>
                ))}
            </select>
            {error && (
                <p className="mt-1 text-sm text-red-600">{error}</p>
            )}
        </div>
    );
}
