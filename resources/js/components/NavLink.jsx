import { Link } from '@inertiajs/react';

export default function NavLink({
    active = false,
    className = '',
    children,
    ...props
}) {
    return (
        <Link
            {...props}
            className={
                'inline-flex items-center border-b-2 px-1 pt-1 text-sm font-medium leading-5 transition duration-150 ease-in-out focus:outline-none ' +
                className +
                ' ' +
                (active
                    ? 'border-brand-gold text-brand-gold'
                    : 'border-transparent text-gray-300 hover:border-brand-gold hover:text-brand-gold')
            }
        >
            {children}
        </Link>
    );
}
