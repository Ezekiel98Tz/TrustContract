import { useState } from 'react';

export default function Tooltip({ label, children }) {
    const [open, setOpen] = useState(false);
    return (
        <span className="relative inline-block">
            <button
                type="button"
                aria-label={label}
                onMouseEnter={() => setOpen(true)}
                onMouseLeave={() => setOpen(false)}
                onFocus={() => setOpen(true)}
                onBlur={() => setOpen(false)}
                className="ml-1 inline-flex items-center justify-center h-4 w-4 rounded-full border border-gray-600 text-xs text-gray-300 hover:text-brand-gold hover:border-brand-gold"
            >
                i
            </button>
            {open && (
                <div role="tooltip" className="absolute z-20 mt-1 px-2 py-1 rounded bg-gray-800 text-gray-200 text-xs border border-gray-700">
                    {children || label}
                </div>
            )}
        </span>
    );
}
