import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm, router, usePage } from '@inertiajs/react';
import StarRating from '@/components/StarRating';
import { useState } from 'react';

export default function Show({ auth, contract, canSign, canReview, downloadable, parties }) {
    const { post, processing, data, setData } = useForm({ rating: 5, comment: '' });
    const [confirmSignOpen, setConfirmSignOpen] = useState(false);
    const [acknowledged, setAcknowledged] = useState(false);
    const user = auth.user;
    const flash = usePage().props.flash;

    const emailOk = !!user.email_verified_at;
    const profileOk = !!user.phone && !!user.country;
    const needsHighValue = !!contract.high_value;
    const levelOk = ['standard', 'advanced'].includes(user.verification_level ?? 'none');

    const toneClass = (tone) => {
        if (tone === 'success') return 'bg-green-900 text-green-200';
        if (tone === 'info') return 'bg-blue-900 text-blue-200';
        if (tone === 'danger') return 'bg-red-900 text-red-200';
        if (tone === 'warning') return 'bg-brand-gold text-brand-black';
        return 'bg-gray-800 text-gray-200';
    };

    const openSignConfirm = () => {
        setAcknowledged(false);
        setConfirmSignOpen(true);
    };

    const closeSignConfirm = () => {
        if (!processing) {
            setConfirmSignOpen(false);
        }
    };

    const confirmSign = () => {
        if (!acknowledged || processing) return;
        post(route('contracts.sign', contract.id), {
            onFinish: () => {
                setConfirmSignOpen(false);
                setAcknowledged(false);
            },
        });
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="text-xl font-semibold leading-tight text-brand-gold">Contract #{contract.id}</h2>}
        >
            <Head title={`Contract #${contract.id}`} />

            <div className="py-12 bg-black min-h-screen">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="bg-brand-black overflow-hidden shadow-lg sm:rounded-lg border border-gray-800">
                        <div className="p-6 text-gray-200">
                            {( !emailOk || !profileOk || (needsHighValue && !levelOk) ) && (
                                <div className="mb-6 rounded-md border border-yellow-700 bg-yellow-900/20 p-4">
                                    <div className="text-sm text-yellow-200 font-semibold">
                                        Action Required
                                    </div>
                                    <div className="mt-1 text-sm text-gray-200">
                                        { !emailOk && <div>Verify your email to proceed.</div> }
                                        { !profileOk && <div>Complete phone and country in Personal Information.</div> }
                                        { needsHighValue && !levelOk && <div>Standard verification required for high-value contracts.</div> }
                                    </div>
                                    <div className="mt-2">
                                        <Link href={route('account.personal-information.edit')} className="inline-flex items-center px-3 py-1 rounded-md bg-brand-gold text-brand-black text-xs font-bold hover:bg-yellow-500">
                                            Go to Personal Information
                                        </Link>
                                    </div>
                                </div>
                            )}
                            {/* Header */}
                            <div className="border-b border-gray-700 pb-6 mb-6">
                                <div className="flex justify-between items-start">
                                    <div>
                                        <h1 className="text-3xl font-bold text-white">{contract.title}</h1>
                                        <p className="text-sm text-gray-400 mt-1">
                                            Created on {new Date(contract.created_at).toLocaleDateString()}
                                        </p>
                                    </div>
                                    <div className="flex flex-col items-end">
                                        <span className={`px-4 py-2 inline-flex text-sm leading-5 font-bold rounded-full uppercase tracking-wider
                                            ${toneClass(contract.status_tone)}`}>
                                            {contract.status_label}
                                        </span>
                                        <div className="mt-2 text-2xl font-bold text-brand-gold">
                                            {(contract.price_cents / 100).toLocaleString('en-US', { style: 'currency', currency: contract.currency || 'USD' })}
                                        </div>
                                    </div>
                                </div>
                                {flash?.success && (
                                    <div className="mt-4 rounded-md border border-green-800 bg-green-900/20 p-4">
                                        <div className="text-sm text-green-200 font-semibold">
                                            {flash.success}
                                        </div>
                                        <div className="mt-2 flex items-center gap-3">
                                            {canSign ? (
                                                <button
                                                    onClick={openSignConfirm}
                                                    disabled={processing}
                                                    className="inline-flex items-center px-4 py-2 bg-brand-gold border border-transparent rounded-md font-bold text-xs text-brand-black uppercase tracking-widest hover:bg-yellow-500"
                                                >
                                                    Sign Now
                                                </button>
                                            ) : (
                                                <span className="text-xs text-gray-300">Waiting for other party or prerequisites.</span>
                                            )}
                                            <Link
                                                href={route('contracts.index')}
                                                className="text-xs text-gray-300 hover:text-white"
                                            >
                                                View My Contracts
                                            </Link>
                                        </div>
                                    </div>
                                )}
                            </div>

                            {/* Details Grid */}
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                                <div>
                                    <h3 className="text-lg font-bold text-brand-gold mb-4 uppercase tracking-wide">Parties</h3>
                                    <div className="bg-gray-900 p-6 rounded-lg border border-gray-700">
                                        <div className="mb-6">
                                            <span className="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Buyer</span>
                                            <div className="flex items-center gap-2">
                                                <span className="block text-lg font-medium text-white">{contract.buyer?.name}</span>
                                                {contract.buyer?.verification_status && (
                                                    <span
                                                        title={contract.buyer.verification_level === 'advanced'
                                                            ? 'Advanced: enhanced checks'
                                                            : contract.buyer.verification_level === 'standard'
                                                            ? 'Standard: ID and address verified'
                                                            : contract.buyer.verification_level === 'basic'
                                                            ? 'Basic: email, phone, country'
                                                            : 'Unverified'}
                                                        className={`inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold border ${
                                                            contract.buyer.verification_status === 'verified'
                                                                ? 'bg-green-900 text-green-200 border-green-800'
                                                                : 'bg-red-900 text-red-200 border-red-800'
                                                        }`}
                                                    >
                                                        {contract.buyer.verification_status === 'verified' ? 'Verified' : 'Unverified'}
                                                        {contract.buyer.verification_level && contract.buyer.verification_level !== 'none' ? ` • ${contract.buyer.verification_level}` : ''}
                                                    </span>
                                                )}
                            {typeof contract.buyer?.rating_avg !== 'undefined' && (
                                <span className="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-gray-800 text-gray-300 border border-gray-700">
                                    {contract.buyer.rating_avg ?? '-'}/5 ({contract.buyer.rating_count})
                                </span>
                            )}
                                            </div>
                                            <span className="block text-sm text-gray-400">{contract.buyer?.email}</span>
                                        </div>
                                        <div>
                                            <span className="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Seller</span>
                                            <div className="flex items-center gap-2">
                                                <span className="block text-lg font-medium text-white">{contract.seller?.name}</span>
                                                {contract.seller?.verification_status && (
                                                    <span
                                                        title={contract.seller.verification_level === 'advanced'
                                                            ? 'Advanced: enhanced checks'
                                                            : contract.seller.verification_level === 'standard'
                                                            ? 'Standard: ID and address verified'
                                                            : contract.seller.verification_level === 'basic'
                                                            ? 'Basic: email, phone, country'
                                                            : 'Unverified'}
                                                        className={`inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold border ${
                                                            contract.seller.verification_status === 'verified'
                                                                ? 'bg-green-900 text-green-200 border-green-800'
                                                                : 'bg-red-900 text-red-200 border-red-800'
                                                        }`}
                                                    >
                                                        {contract.seller.verification_status === 'verified' ? 'Verified' : 'Unverified'}
                                                        {contract.seller.verification_level && contract.seller.verification_level !== 'none' ? ` • ${contract.seller.verification_level}` : ''}
                                                    </span>
                                                )}
                            {typeof contract.seller?.rating_avg !== 'undefined' && (
                                <span className="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-gray-800 text-gray-300 border border-gray-700">
                                    {contract.seller.rating_avg ?? '-'}/5 ({contract.seller.rating_count})
                                </span>
                            )}
                                            </div>
                                            <span className="block text-sm text-gray-400">{contract.seller?.email}</span>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <h3 className="text-lg font-bold text-brand-gold mb-4 uppercase tracking-wide">Terms</h3>
                                    <div className="prose max-w-none text-gray-300 bg-gray-900 p-6 rounded-lg border border-gray-700 min-h-[200px]">
                                        {contract.description || "No description provided."}
                                    </div>
                                    {contract.deadline_at && (
                                        <div className="mt-4 flex items-center">
                                            <span className="text-xs font-bold text-gray-500 uppercase mr-2">Deadline: </span>
                                            <span className="text-white font-medium">{new Date(contract.deadline_at).toLocaleDateString()}</span>
                                        </div>
                                    )}
                                </div>
                            </div>

                            {canReview && (
                                <div className="bg-brand-black overflow-hidden shadow-lg sm:rounded-lg border border-gray-800 mb-8">
                                    <div className="p-6 text-gray-200">
                                        <h3 className="text-lg font-bold text-brand-gold uppercase tracking-wide">Leave a Review</h3>
                                        <form
                                            onSubmit={(e) => {
                                                e.preventDefault();
                                                post(route('contracts.reviews.store', contract.id));
                                            }}
                                            className="mt-4 space-y-4"
                                        >
                                            <div>
                                                <label className="text-sm text-gray-300">Rating (1-5)</label>
                                                <div className="mt-2">
                                                    <StarRating
                                                        value={data.rating}
                                                        onChange={(v) => setData('rating', v)}
                                                        size={28}
                                                    />
                                                </div>
                                            </div>
                                            <div>
                                                <label className="text-sm text-gray-300">Comment</label>
                                                <textarea
                                                    value={data.comment}
                                                    onChange={(e) => setData('comment', e.target.value)}
                                                    className="mt-1 block w-full rounded-md bg-gray-900 border-gray-800 text-gray-200"
                                                    rows={3}
                                                />
                                            </div>
                                            <button
                                                type="submit"
                                                disabled={processing}
                                                className="inline-flex items-center px-6 py-3 bg-brand-gold border border-transparent rounded-md font-bold text-sm text-brand-black uppercase tracking-widest hover:bg-yellow-500"
                                            >
                                                Submit Review
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            )}

                            {/* Reviews */}
                            {contract.reviews && contract.reviews.length > 0 && (
                                <div className="border-t border-gray-700 pt-6 mb-8">
                                    <h3 className="text-lg font-bold text-brand-gold mb-4 uppercase tracking-wide">Reviews</h3>
                                    <ul className="space-y-4">
                                        {contract.reviews.map((rv) => (
                                            <li key={rv.id} className="bg-gray-900 p-4 rounded-lg border border-gray-700">
                                                <div className="flex items-center justify-between">
                                                    <div className="text-sm text-gray-400">By {rv.reviewer?.name ?? `User #${rv.reviewer_id}`}</div>
                                                    <StarRating value={rv.rating} readOnly size={18} />
                                                </div>
                                                {rv.comment && <p className="mt-2 text-gray-200 text-sm">{rv.comment}</p>}
                                                <div className="mt-2 text-xs text-gray-500">{new Date(rv.created_at).toLocaleString()}</div>
                                            </li>
                                        ))}
                                    </ul>
                                </div>
                            )}

                            {/* Party Insights */}
                            <div className="border-t border-gray-700 pt-6 mb-8">
                                <h3 className="text-lg font-bold text-brand-gold mb-4 uppercase tracking-wide">Party Insights</h3>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div className="bg-gray-900 p-4 rounded-lg border border-gray-700">
                                        <h4 className="text-sm font-bold text-gray-300 uppercase tracking-wide">Buyer Recent</h4>
                                        <div className="mt-2">
                                            <Link
                                                href={route('counterparties.reviews', contract.buyer_id)}
                                                className="inline-flex items-center px-3 py-1 bg-brand-gold rounded-md text-brand-black text-xs font-bold hover:bg-yellow-500"
                                            >
                                                View Full Reviews
                                            </Link>
                                        </div>
                                        <div className="mt-2">
                                            <div className="text-xs font-semibold text-gray-400">Reviews</div>
                                            {parties?.buyer?.recent_reviews?.length ? (
                                                <ul className="mt-1 space-y-2">
                                                    {parties.buyer.recent_reviews.map((rv) => (
                                                        <li key={rv.id} className="bg-gray-800 p-2 rounded border border-gray-700">
                                                            <div className="flex items-center justify-between">
                                                                <div className="text-xs text-gray-400">{rv.reviewer?.name || 'User'}</div>
                                                                <StarRating value={rv.rating} readOnly size={14} />
                                                            </div>
                                                            {rv.comment && <p className="mt-1 text-[12px] text-gray-200">{rv.comment}</p>}
                                                        </li>
                                                    ))}
                                                </ul>
                                            ) : <p className="text-xs text-gray-500 mt-1">No reviews.</p>}
                                        </div>
                                        <div className="mt-3">
                                            <div className="text-xs font-semibold text-gray-400">Completed Contracts</div>
                                            {parties?.buyer?.recent_history?.length ? (
                                                <ul className="mt-1 space-y-2">
                                                    {parties.buyer.recent_history.map((h) => (
                                                        <li key={h.id} className="bg-gray-800 p-2 rounded border border-gray-700">
                                                            <div className="text-xs text-gray-200 font-semibold">{h.title}</div>
                                                            <div className="text-[11px] text-gray-400">{(h.price_cents / 100).toLocaleString('en-US', { style: 'currency', currency: h.currency })}</div>
                                                        </li>
                                                    ))}
                                                </ul>
                                            ) : <p className="text-xs text-gray-500 mt-1">No history.</p>}
                                        </div>
                                    </div>
                                    <div className="bg-gray-900 p-4 rounded-lg border border-gray-700">
                                        <h4 className="text-sm font-bold text-gray-300 uppercase tracking-wide">Seller Recent</h4>
                                        <div className="mt-2">
                                            <Link
                                                href={route('counterparties.reviews', contract.seller_id)}
                                                className="inline-flex items-center px-3 py-1 bg-brand-gold rounded-md text-brand-black text-xs font-bold hover:bg-yellow-500"
                                            >
                                                View Full Reviews
                                            </Link>
                                        </div>
                                        <div className="mt-2">
                                            <div className="text-xs font-semibold text-gray-400">Reviews</div>
                                            {parties?.seller?.recent_reviews?.length ? (
                                                <ul className="mt-1 space-y-2">
                                                    {parties.seller.recent_reviews.map((rv) => (
                                                        <li key={rv.id} className="bg-gray-800 p-2 rounded border border-gray-700">
                                                            <div className="flex items-center justify-between">
                                                                <div className="text-xs text-gray-400">{rv.reviewer?.name || 'User'}</div>
                                                                <StarRating value={rv.rating} readOnly size={14} />
                                                            </div>
                                                            {rv.comment && <p className="mt-1 text-[12px] text-gray-200">{rv.comment}</p>}
                                                        </li>
                                                    ))}
                                                </ul>
                                            ) : <p className="text-xs text-gray-500 mt-1">No reviews.</p>}
                                        </div>
                                        <div className="mt-3">
                                            <div className="text-xs font-semibold text-gray-400">Completed Contracts</div>
                                            {parties?.seller?.recent_history?.length ? (
                                                <ul className="mt-1 space-y-2">
                                                    {parties.seller.recent_history.map((h) => (
                                                        <li key={h.id} className="bg-gray-800 p-2 rounded border border-gray-700">
                                                            <div className="text-xs text-gray-200 font-semibold">{h.title}</div>
                                                            <div className="text-[11px] text-gray-400">{(h.price_cents / 100).toLocaleString('en-US', { style: 'currency', currency: h.currency })}</div>
                                                        </li>
                                                    ))}
                                                </ul>
                                            ) : <p className="text-xs text-gray-500 mt-1">No history.</p>}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {/* Signatures */}
                            <div className="border-t border-gray-700 pt-6 mb-8">
                                <h3 className="text-lg font-bold text-brand-gold mb-4 uppercase tracking-wide">Signatures</h3>
                                {contract.signatures && contract.signatures.length > 0 ? (
                                    <ul className="space-y-3">
                                        {contract.signatures.map((sig) => (
                                            <li key={sig.id} className="flex items-center text-sm text-gray-200 bg-green-900/20 p-3 rounded-md border border-green-900/50">
                                                <svg className="h-5 w-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                                <span className="font-medium">Signed by {sig.user?.name || `User #${sig.user_id}`}</span>
                                                <span className="mx-2 text-gray-600">|</span>
                                                <span>{new Date(sig.signed_at).toLocaleString()}</span>
                                            </li>
                                        ))}
                                    </ul>
                                ) : (
                                    <p className="text-sm text-gray-500 italic">No signatures yet. Waiting for parties to sign.</p>
                                )}
                            </div>

                            {/* Contract Logs (Audit Trail) */}
                            {contract.logs && contract.logs.length > 0 && (
                                <div className="border-t border-gray-700 pt-6">
                                    <h3 className="text-lg font-bold text-brand-gold mb-4 uppercase tracking-wide">Activity Log</h3>
                                    <div className="flow-root">
                                        <ul className="-mb-8">
                                            {contract.logs.map((log, logIdx) => (
                                                <li key={log.id}>
                                                    <div className="relative pb-8">
                                                        {logIdx !== contract.logs.length - 1 ? (
                                                            <span className="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-700" aria-hidden="true" />
                                                        ) : null}
                                                        <div className="relative flex space-x-3">
                                                            <div>
                                                                <span className="h-8 w-8 rounded-full bg-gray-800 flex items-center justify-center ring-8 ring-brand-black border border-gray-600">
                                                                    <svg className="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                                    </svg>
                                                                </span>
                                                            </div>
                                                            <div className="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                                                <div>
                                                                    <p className="text-sm text-gray-300">
                                                                        <span className="font-medium text-white">{log.action_label}</span> by <span className="font-medium text-white">{log.actor?.name || 'System'}</span>
                                                                    </p>
                                                                    {(log.from_status_label || log.to_status_label) && (
                                                                        <p className="text-xs text-gray-500">
                                                                            Status: <span className="text-gray-300">{log.from_status_label || '—'}</span> →{' '}
                                                                            <span className="text-gray-300">{log.to_status_label || '—'}</span>
                                                                        </p>
                                                                    )}
                                                                </div>
                                                                <div className="text-right text-sm whitespace-nowrap text-gray-500">
                                                                    <time dateTime={log.created_at}>{new Date(log.created_at).toLocaleString()}</time>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </li>
                                            ))}
                                        </ul>
                                    </div>
                                </div>
                            )}
                        </div>
                        
                        {/* Footer Actions */}
                        <div className="bg-gray-900 px-6 py-4 flex justify-between items-center border-t border-gray-800">
                            <Link
                                href={route('contracts.index')}
                                className="text-gray-400 hover:text-white font-medium text-sm flex items-center transition duration-150 ease-in-out"
                            >
                                &larr; Back to Contracts
                            </Link>
                            
                            <div className="flex space-x-4">
                                {/* Sign Contract Button - Prominent */}
                                {canSign && (
                                    <button
                                        onClick={openSignConfirm}
                                        disabled={processing}
                                        className="inline-flex items-center px-6 py-3 bg-brand-gold border border-transparent rounded-md font-bold text-sm text-brand-black uppercase tracking-widest hover:bg-yellow-500 focus:bg-yellow-500 active:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-brand-gold focus:ring-offset-2 transition ease-in-out duration-150 shadow-lg transform hover:-translate-y-0.5 disabled:opacity-50"
                                        title="Sign this contract digitally"
                                    >
                                        {processing ? 'Signing...' : 'Sign Contract'}
                                    </button>
                                )}

                                {/* Download and Delete */}
                                {downloadable && (
                                    <Link
                                        href={`${route('contracts.print', contract.id)}?download=1`}
                                        target="_blank"
                                        className="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-gray-200 uppercase tracking-widest hover:bg-gray-700 focus:outline-none transition ease-in-out duration-150"
                                    >
                                        Download PDF
                                    </Link>
                                )}
                                {downloadable && (
                                    <Link
                                        href={route('contracts.print', contract.id)}
                                        className="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-gray-200 uppercase tracking-widest hover:bg-gray-700 focus:outline-none transition ease-in-out duration-150"
                                    >
                                        Printable Version
                                    </Link>
                                )}
                                <button
                                    onClick={() => {
                                        if (confirm('Deleting this contract removes the agreement from storage. You are responsible for deletion. Continue?')) {
                                            router.delete(route('contracts.destroy', contract.id));
                                        }
                                    }}
                                    className="inline-flex items-center px-4 py-2 bg-red-900 border border-red-800 rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-800"
                                >
                                    Delete Contract
                                </button>
                                {/* Admin Actions */}
                                {auth.user.role === 'Admin' && contract.status_label !== 'Finalized' && (
                                    <button
                                        className="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-brand-gold uppercase tracking-widest hover:bg-gray-700 focus:outline-none transition ease-in-out duration-150"
                                    >
                                        Manage Contract
                                    </button>
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {confirmSignOpen && (
                <div className="fixed inset-0 z-50 flex items-center justify-center px-4">
                    <div
                        className="absolute inset-0 bg-black/70"
                        onClick={closeSignConfirm}
                        aria-hidden="true"
                    />
                    <div className="relative w-full max-w-lg rounded-xl bg-brand-black border border-gray-800 shadow-2xl">
                        <div className="p-6">
                            <h3 className="text-xl font-bold text-brand-gold">Confirm your signature</h3>
                            <p className="mt-2 text-sm text-gray-300">
                                Signing is legally binding. Please review the contract terms and confirm you understand before signing.
                            </p>

                            <div className="mt-4 rounded-lg border border-gray-800 bg-gray-900 p-4">
                                <div className="flex items-start gap-3">
                                    <input
                                        id="acknowledge-sign"
                                        type="checkbox"
                                        checked={acknowledged}
                                        onChange={(e) => setAcknowledged(e.target.checked)}
                                        className="mt-1 h-4 w-4 rounded border-gray-700 bg-gray-800 text-brand-gold focus:ring-brand-gold"
                                    />
                                    <label htmlFor="acknowledge-sign" className="text-sm text-gray-300">
                                        I understand that this action is legally binding and I agree to the terms.
                                    </label>
                                </div>
                            </div>

                            <div className="mt-6 flex items-center justify-end gap-3">
                                <button
                                    type="button"
                                    onClick={closeSignConfirm}
                                    disabled={processing}
                                    className="inline-flex items-center px-4 py-2 rounded-md border border-gray-700 bg-gray-900 text-gray-200 font-semibold text-sm hover:bg-gray-800 disabled:opacity-50"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="button"
                                    onClick={confirmSign}
                                    disabled={!acknowledged || processing}
                                    className="inline-flex items-center px-5 py-2 rounded-md bg-brand-gold text-brand-black font-bold text-sm hover:bg-yellow-500 disabled:opacity-50"
                                >
                                    {processing ? 'Signing…' : 'Sign & Agree'}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </AuthenticatedLayout>
    );
}
