import ApplicationLogo from '@/components/ApplicationLogo';
import { Link } from '@inertiajs/react';

export default function GuestLayout({ children }) {
    return (
        <div className="flex min-h-screen flex-col items-center bg-black pt-6 sm:justify-center sm:pt-0">
            <div>
                <Link href="/">
                    <ApplicationLogo className="h-20 w-20 fill-current text-brand-gold" />
                </Link>
            </div>

            <div className="mt-6 w-full overflow-hidden bg-brand-black border border-gray-800 px-6 py-4 shadow-md sm:max-w-md sm:rounded-lg">
                <div className="text-gray-300">
                    {children}
                </div>
            </div>
        </div>
    );
}
