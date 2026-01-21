import ApplicationLogo from '@/components/ApplicationLogo';
import { Link, usePage } from '@inertiajs/react';
import { useEffect, useState, useRef } from 'react';

export default function AuthenticatedLayout({ header, children }) {
    const user = usePage().props.auth.user;
    const { flash, notifications } = usePage().props;
    const [showFlash, setShowFlash] = useState(true);
    const [collapsed, setCollapsed] = useState(false);
    const isAdmin = user.role === 'Admin';
    const [userMenuOpen, setUserMenuOpen] = useState(false);
    const userMenuRef = useRef(null);

    useEffect(() => {
        if (!flash?.success && !flash?.error) return;
        setShowFlash(true);
        const t = setTimeout(() => setShowFlash(false), 5000);
        return () => clearTimeout(t);
    }, [flash?.success, flash?.error]);

    const Icon = ({ name, className }) => {
        const common = "h-5 w-5";
        if (name === 'dashboard') return <svg className={`${common} ${className}`} viewBox="0 0 24 24" fill="none" stroke="currentColor"><path strokeWidth="2" d="M3 13h8V3H3v10zm10 8h8V11h-8v10zM3 21h8v-6H3v6zm10-12h8V3h-8v6z"/></svg>;
        if (name === 'contracts') return <svg className={`${common} ${className}`} viewBox="0 0 24 24" fill="none" stroke="currentColor"><path strokeWidth="2" d="M8 6h8M8 10h8M8 14h5M5 3h14a2 2 0 012 2v14l-4-4H5a2 2 0 01-2-2V5a2 2 0 012-2z"/></svg>;
        if (name === 'info') return <svg className={`${common} ${className}`} viewBox="0 0 24 24" fill="none" stroke="currentColor"><path strokeWidth="2" d="M12 8v.01M12 12v8m0-20a10 10 0 100 20 10 10 0 000-20z"/></svg>;
        if (name === 'devices') return <svg className={`${common} ${className}`} viewBox="0 0 24 24" fill="none" stroke="currentColor"><path strokeWidth="2" d="M9 18h6M4 6h16v10H4V6zm4 14h8"/></svg>;
        if (name === 'notifications') return <svg className={`${common} ${className}`} viewBox="0 0 24 24" fill="none" stroke="currentColor"><path strokeWidth="2" d="M12 22a2 2 0 002-2H10a2 2 0 002 2zm6-6V9a6 6 0 10-12 0v7l-2 2h16l-2-2z"/></svg>;
        if (name === 'users') return <svg className={`${common} ${className}`} viewBox="0 0 24 24" fill="none" stroke="currentColor"><path strokeWidth="2" d="M17 20v-2a4 4 0 00-4-4H7a4 4 0 00-4 4v2m14-10a4 4 0 11-8 0 4 4 0 018 0z"/></svg>;
        if (name === 'verify') return <svg className={`${common} ${className}`} viewBox="0 0 24 24" fill="none" stroke="currentColor"><path strokeWidth="2" d="M9 12l2 2 4-4M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"/></svg>;
        return null;
    };
    const navItem = (href, label, active, icon) => (
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
            <Icon name={icon} className={collapsed ? '' : 'text-brand-gold'} />
            {!collapsed && <span>{label}</span>}
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
                className={`sticky top-0 h-screen border-r border-gray-800 bg-brand-black flex flex-col ${collapsed ? 'w-16' : 'w-64'} transition-all duration-200 overflow-y-auto`}
            >
                <div className={`${collapsed ? 'flex flex-col items-center gap-2' : 'flex items-center justify-between'} px-3 py-3`}>
                    <Link href="/" className="flex items-center gap-2 min-w-0">
                        <ApplicationLogo className="h-8 w-8 shrink-0 fill-current text-brand-gold" />
                        {!collapsed && <span className="text-brand-gold font-bold truncate">TrustContract</span>}
                    </Link>
                    <button
                        onClick={() => setCollapsed(!collapsed)}
                        className={`${collapsed ? 'rounded-md p-2 text-gray-400 hover:text-brand-gold hover:bg-gray-800' : 'ms-2 shrink-0 rounded-md p-2 text-gray-400 hover:text-brand-gold hover:bg-gray-800'}`}
                        title={collapsed ? 'Expand' : 'Collapse'}
                    >
                        <svg className="h-5 w-5" viewBox="0 0 24 24" stroke="currentColor" fill="none">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d={collapsed ? 'M4 6h16M4 12h16M4 18h16' : 'M6 18L18 6M6 6l12 12'} />
                        </svg>
                    </button>
                </div>
                <nav className="px-2">
                    {navItem(route('dashboard'), 'Dashboard', route().current('dashboard'), 'dashboard')}
                    {navItem(route('contracts.index'), 'Contracts', route().current('contracts.*'), 'contracts')}
                    {navItem(route('account.personal-information.edit'), 'Personal Information', route().current('account.personal-information.*'), 'info')}
                    {navItem(route('account.devices.index'), 'Devices', route().current('account.devices.*'), 'devices')}
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
                        <Icon name="notifications" className={collapsed ? '' : 'text-brand-gold'} />
                        {!collapsed && <span>Notifications</span>}
                        {notifications?.unread_count > 0 && (
                            <span className="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-red-900 text-red-200 border border-red-800">
                                {notifications.unread_count}
                            </span>
                        )}
                    </Link>
                    {isAdmin && navItem(route('admin.users.index'), 'Users (Admin)', route().current('admin.users.*'), 'users')}
                    {isAdmin && navItem(route('admin.verifications.index'), 'Verifications (Admin)', route().current('admin.verifications.*'), 'verify')}
                </nav>
                <div className="px-3">{trustBadge()}</div>
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

                <header className="bg-brand-black border-b border-gray-800">
                    <div className="px-4 py-3 flex items-center justify-between">
                        <div className="flex items-center gap-2">
                            {header ? <div className="text-brand-gold font-semibold">{header}</div> : <div className="text-brand-gold font-semibold">TrustContract</div>}
                        </div>
                        <div className="relative" ref={userMenuRef}>
                            <button
                                onClick={() => setUserMenuOpen(!userMenuOpen)}
                                className="flex items-center gap-3 rounded-full hover:bg-gray-800 px-2 py-1"
                            >
                                <span className="inline-flex h-8 w-8 items-center justify-center rounded-full bg-gray-700 text-white font-bold">
                                    {user.name?.[0]?.toUpperCase() || 'U'}
                                </span>
                                <div className="hidden sm:block text-right">
                                    <div className="text-sm font-semibold text-brand-gold">{user.name} ({user.role})</div>
                                    <div className="text-xs text-gray-500">{user.email}</div>
                                </div>
                                <svg className="h-4 w-4 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path strokeWidth="2" d="M6 9l6 6 6-6"/></svg>
                            </button>
                            {userMenuOpen && (
                                <div className="absolute right-0 mt-2 w-44 rounded-md border border-gray-800 bg-brand-black shadow-lg z-50">
                                    <Link href={route('profile.edit')} className="block px-3 py-2 text-sm text-gray-200 hover:bg-gray-800">Profile</Link>
                                    <Link href={route('logout')} method="post" as="button" className="block px-3 py-2 text-sm text-red-300 hover:bg-gray-800">Log Out</Link>
                                </div>
                            )}
                        </div>
                    </div>
                </header>

                <main className="flex-1 overflow-y-auto">{children}</main>
            </div>
        </div>
    );
}
