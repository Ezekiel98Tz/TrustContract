import { Link } from '@inertiajs/react';

export default function ResponsiveNavLink({
    active = false,
    className = '',
    children,
    ...props
}) {
    return (
        <Link
            {...props}
            className={`${className} flex w-full items-start border-l-4 py-2 pe-4 ps-3 ${
                active
                    ? 'border-brand-gold bg-gray-900 text-brand-gold'
                    : 'border-transparent text-gray-300 hover:border-brand-gold hover:bg-gray-900 hover:text-brand-gold'
            } text-base font-medium transition duration-150 ease-in-out focus:outline-none`}
        >
            {children}
        </Link>
    );
}
