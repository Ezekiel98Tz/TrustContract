import ApplicationLogo from '@/components/ApplicationLogo';
import { Link, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

export default function AuthenticatedLayout({ header, children }) {
    const user = usePage().props.auth.user;
    const { flash, notifications } = usePage().props;
    const [showFlash, setShowFlash] = useState(true);
    const [collapsed, setCollapsed] = useState(false);
    const isAdmin = user.role === 'Admin';

    useEffect(() => {
        if (!flash?.success && !flash?.error) return;
        setShowFlash(true);
        const t = setTimeout(() => setShowFlash(false), 5000);
        return () => clearTimeout(t);
    }, [flash?.success, flash?.error]);

    const navItem = (href, label, active, abbr) => (
        <Link
            href={href}
            title={label}
            className={
                'flex items-center ' +
                (collapsed ? 'justify-center' : 'gap-3') +
                ' px-3 py-2 rounded-md text-sm font-medium transition-colors ' +
                (active
                    ? 'bg-gray-800 text-brand-gold'
                    : 'text-gray-300 hover:text-brand-gold hover:bg-gray-800')
            }
        >
            {collapsed ? (
                <span className="font-bold">{abbr}</span>
            ) : (
                <span>{label}</span>
            )}
        </Link>
    );

    const trustBadge = () => {
        const emailOk = !!user.email_verified_at;
        const profileOk = !!user.phone && !!user.country;
        const level = user.verification_level ?? 'none';
        const vstatus = user.verification_status ?? 'unverified';
        const needsAttention = !emailOk || !profileOk || level === 'none';
        if (collapsed) {
            return (
                <div className="mt-4 px-3">
                    <div
                        title="Trust Status"
                        className={`rounded-md border px-2 py-1 text-center text-xs ${needsAttention ? 'border-yellow-700 bg-yellow-900/20' : 'border-gray-700 bg-gray-800/40'} text-gray-200`}
                    >
                        {vstatus === 'verified' ? 'V' : 'U'}{level && level !== 'none' ? `/${level[0].toUpperCase()}` : ''}
                    </div>
                </div>
            );
        }
        return (
            <div className="mt-4">
                <div className={`rounded-md border px-3 py-2 ${needsAttention ? 'border-yellow-700 bg-yellow-900/20' : 'border-gray-700 bg-gray-800/40'}`}>
                    <div className="text-xs font-semibold uppercase tracking-wider text-gray-400">
                        Trust Status
                    </div>
                    <div className="mt-1 text-sm text-gray-200">
                        {vstatus === 'verified' ? 'Verified' : 'Unverified'}{level && level !== 'none' ? ` • ${level}` : ''}
                    </div>
                    {needsAttention && (
                        <div className="mt-2">
                            <Link
                                href={route('account.personal-information.edit')}
                                className="inline-flex items-center px-2 py-1 rounded-md bg-brand-gold text-brand-black text-xs font-bold hover:bg-yellow-500"
                            >
                                Complete Personal Information
                            </Link>
                        </div>
                    )}
                </div>
            </div>
        );
    };

    return (
        <div className="min-h-screen bg-brand-black text-brand-white flex">
            {/* Sidebar */}
            <aside
                className={`sticky top-0 h-screen border-r border-gray-800 bg-brand-black flex flex-col ${collapsed ? 'w-16' : 'w-64'} transition-all duration-200 overflow-hidden`}
            >
                <div className="flex items-center justify-between px-3 py-3">
                    <Link href="/" className="flex items-center gap-2">
                        <ApplicationLogo className="h-8 w-8 fill-current text-brand-gold" />
                        {!collapsed && <span className="text-brand-gold font-bold">TrustContract</span>}
                    </Link>
                    <button
                        onClick={() => setCollapsed(!collapsed)}
                        className="rounded-md p-2 text-gray-400 hover:text-brand-gold hover:bg-gray-800"
                        title={collapsed ? 'Expand' : 'Collapse'}
                    >
                        <svg className="h-5 w-5" viewBox="0 0 24 24" stroke="currentColor" fill="none">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d={collapsed ? 'M4 6h16M4 12h16M4 18h16' : 'M6 18L18 6M6 6l12 12'} />
                        </svg>
                    </button>
                </div>
                <nav className="px-2">
                    {navItem(route('dashboard'), 'Dashboard', route().current('dashboard'), 'D')}
                    {navItem(route('contracts.index'), 'Contracts', route().current('contracts.*'), 'C')}
                    {navItem(route('account.personal-information.edit'), 'Personal Information', route().current('account.personal-information.*'), 'PI')}
                    {navItem(route('account.devices.index'), 'Devices', route().current('account.devices.*'), 'DV')}
                    <Link
                        href={route('notifications.index')}
                        title="Notifications"
                        className={
                            'flex items-center ' +
                            (collapsed ? 'justify-center' : 'gap-3') +
                            ' px-3 py-2 rounded-md text-sm font-medium transition-colors ' +
                            (route().current('notifications.*')
                                ? 'bg-gray-800 text-brand-gold'
                                : 'text-gray-300 hover:text-brand-gold hover:bg-gray-800')
                        }
                    >
                        {collapsed ? (
                            <span className="font-bold">N</span>
                        ) : (
                            <span>Notifications</span>
                        )}
                        {notifications?.unread_count > 0 && (
                            <span className="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-red-900 text-red-200 border border-red-800">
                                {notifications.unread_count}
                            </span>
                        )}
                    </Link>
                    {isAdmin && navItem(route('admin.users.index'), 'Users (Admin)', route().current('admin.users.*'), 'U')}
                    {isAdmin && navItem(route('admin.verifications.index'), 'Verifications (Admin)', route().current('admin.verifications.*'), 'V')}
                </nav>
                <div className="px-3">{trustBadge()}</div>
                <div className="mt-auto px-3 py-3 border-t border-gray-800">
                    <div className="text-sm text-gray-300">
                        {!collapsed && (
                            <div>
                                <div className="font-semibold text-brand-gold">{user.name}</div>
                                <div className="text-gray-500">{user.email}</div>
                            </div>
                        )}
                    </div>
                    <Link
                        href={route('profile.edit')}
                        className="mt-2 block text-sm text-gray-300 hover:text-brand-gold"
                    >
                        {!collapsed ? 'Profile' : 'P'}
                    </Link>
                    <Link
                        href={route('logout')}
                        method="post"
                        as="button"
                        className="mt-1 block text-sm text-gray-300 hover:text-brand-gold"
                    >
                        {!collapsed ? 'Log Out' : '↩'}
                    </Link>
                </div>
            </aside>

            {/* Main content */}
            <div className="flex-1 flex flex-col min-w-0">
                {showFlash && flash?.success && (
                    <div className="bg-brand-gold text-brand-black px-4 py-3 text-center font-bold">
                        {flash.success}
                    </div>
                )}
                {showFlash && flash?.error && (
                    <div className="bg-red-900 text-white px-4 py-3 text-center font-bold border-b border-red-700">
                        {flash.error}
                    </div>
                )}

                {header && (
                    <header className="bg-brand-black border-b border-gray-800">
                        <div className="px-4 py-4 text-brand-gold">{header}</div>
                    </header>
                )}

                <main className="flex-1 overflow-y-auto">{children}</main>
            </div>
        </div>
    );
}
