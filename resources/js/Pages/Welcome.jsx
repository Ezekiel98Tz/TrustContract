import { Head, Link } from '@inertiajs/react';
import ApplicationLogo from '@/components/ApplicationLogo';

export default function Welcome({ auth, laravelVersion, phpVersion }) {
    return (
        <>
            <Head title="Welcome" />
            <div className="bg-black text-white min-h-screen selection:bg-brand-gold selection:text-black">
                <div className="relative flex min-h-screen flex-col items-center justify-center">
                    <div className="relative w-full max-w-2xl px-6 lg:max-w-7xl">
                        <header className="grid grid-cols-2 items-center gap-2 py-10 lg:grid-cols-3">
                            <div className="flex lg:col-start-2 lg:justify-center">
                                <ApplicationLogo className="h-16 w-auto text-brand-gold fill-current" />
                            </div>
                            <nav className="-mx-3 flex flex-1 justify-end">
                                {auth.user ? (
                                    <Link
                                        href={route('dashboard')}
                                        className="rounded-md px-3 py-2 text-white ring-1 ring-transparent transition hover:text-brand-gold focus:outline-none focus-visible:ring-brand-gold"
                                    >
                                        Dashboard
                                    </Link>
                                ) : (
                                    <>
                                        <Link
                                            href={route('login')}
                                            className="rounded-md px-3 py-2 text-white ring-1 ring-transparent transition hover:text-brand-gold focus:outline-none focus-visible:ring-brand-gold"
                                        >
                                            Log in
                                        </Link>
                                        <Link
                                            href={route('register')}
                                            className="rounded-md px-3 py-2 text-white ring-1 ring-transparent transition hover:text-brand-gold focus:outline-none focus-visible:ring-brand-gold"
                                        >
                                            Register
                                        </Link>
                                    </>
                                )}
                            </nav>
                        </header>

                        <main className="mt-6">
                            <div className="text-center py-16 lg:py-32">
                                <h1 className="text-4xl font-extrabold tracking-tight text-brand-gold sm:text-5xl md:text-6xl">
                                    TrustContract
                                </h1>
                                <p className="mt-6 max-w-2xl mx-auto text-xl text-gray-400">
                                    Secure, fast, and reliable digital contract management. 
                                    Create, sign, and verify contracts with ease.
                                </p>
                                <div className="mt-10 flex justify-center gap-4">
                                    {auth.user ? (
                                        <Link
                                            href={route('contracts.create')}
                                            className="rounded-md bg-brand-gold px-8 py-3 text-base font-bold text-black shadow hover:bg-yellow-500 focus:outline-none focus:ring-2 focus:ring-brand-gold focus:ring-offset-2 focus:ring-offset-black"
                                        >
                                            Create Contract
                                        </Link>
                                    ) : (
                                        <Link
                                            href={route('register')}
                                            className="rounded-md bg-brand-gold px-8 py-3 text-base font-bold text-black shadow hover:bg-yellow-500 focus:outline-none focus:ring-2 focus:ring-brand-gold focus:ring-offset-2 focus:ring-offset-black"
                                        >
                                            Get Started
                                        </Link>
                                    )}
                                </div>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-3 gap-8 px-4 py-12">
                                <div className="bg-brand-black p-6 rounded-lg border border-gray-800 text-center">
                                    <div className="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-gray-900 text-brand-gold mb-4">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-6 h-6">
                                            <path strokeLinecap="round" strokeLinejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                        </svg>
                                    </div>
                                    <h3 className="text-lg font-bold text-white">Create</h3>
                                    <p className="mt-2 text-gray-400">Draft contracts easily with our intuitive interface.</p>
                                </div>
                                <div className="bg-brand-black p-6 rounded-lg border border-gray-800 text-center">
                                    <div className="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-gray-900 text-brand-gold mb-4">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-6 h-6">
                                            <path strokeLinecap="round" strokeLinejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                        </svg>
                                    </div>
                                    <h3 className="text-lg font-bold text-white">Sign</h3>
                                    <p className="mt-2 text-gray-400">Secure digital signatures for both parties.</p>
                                </div>
                                <div className="bg-brand-black p-6 rounded-lg border border-gray-800 text-center">
                                    <div className="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-gray-900 text-brand-gold mb-4">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-6 h-6">
                                            <path strokeLinecap="round" strokeLinejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z" />
                                        </svg>
                                    </div>
                                    <h3 className="text-lg font-bold text-white">Verify</h3>
                                    <p className="mt-2 text-gray-400">Admin verification ensures trust and safety.</p>
                                </div>
                            </div>
                        </main>

                        <footer className="py-16 text-center text-sm text-gray-500">
                            &copy; {new Date().getFullYear()} TrustContract. All rights reserved.
                        </footer>
                    </div>
                </div>
            </div>
        </>
    );
}
