import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import StarRating from '@/components/StarRating';

export default function Reviews({ auth, user, rating_avg, rating_count, reviews }) {
    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="text-xl font-semibold leading-tight text-brand-gold">Reviews for {user.name}</h2>}
        >
            <Head title={`Reviews: ${user.name}`} />
            <div className="py-12 bg-black min-h-screen">
                <div className="mx-auto max-w-5xl sm:px-6 lg:px-8">
                    <div className="bg-brand-black overflow-hidden shadow-lg sm:rounded-lg border border-gray-800">
                        <div className="p-6 text-gray-200">
                            <div className="flex items-center justify-between">
                                <div>
                                    <div className="text-sm text-gray-400">{user.email} • {user.role}</div>
                                    <div className="mt-1 flex items-center gap-2">
                                        <span className={`inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold border ${
                                            user.verification_status === 'verified'
                                                ? 'bg-green-900 text-green-200 border-green-800'
                                                : 'bg-red-900 text-red-200 border-red-800'
                                        }`}>
                                            {user.verification_status === 'verified' ? 'Verified' : 'Unverified'}{user.verification_level && user.verification_level !== 'none' ? ` • ${user.verification_level}` : ''}
                                        </span>
                                        <span className="inline-flex items-center">
                                            <StarRating value={rating_avg || 0} readOnly size={18} />
                                            <span className="ml-2 text-sm text-gray-300">{rating_avg ?? '-'}/5</span>
                                            <span className="ml-2 text-sm text-gray-500">({rating_count} reviews)</span>
                                        </span>
                                    </div>
                                </div>
                                <Link href={route('contracts.create')} className="text-xs text-brand-gold hover:text-white">Back to Create</Link>
                            </div>
                            <div className="mt-6">
                                {reviews.data.length === 0 ? (
                                    <div className="text-sm text-gray-500">No reviews yet.</div>
                                ) : (
                                    <ul className="space-y-3">
                                        {reviews.data.map((rv) => (
                                            <li key={rv.id} className="bg-gray-900 p-4 rounded-lg border border-gray-700">
                                                <div className="flex items-center justify-between">
                                                    <div className="text-sm text-gray-400">By {rv.reviewer?.name || 'User'}</div>
                                                    <StarRating value={rv.rating} readOnly size={16} />
                                                </div>
                                                {rv.comment && <p className="mt-2 text-gray-200 text-sm">{rv.comment}</p>}
                                                <div className="mt-2 text-xs text-gray-500">{new Date(rv.created_at).toLocaleString()}</div>
                                            </li>
                                        ))}
                                    </ul>
                                )}
                                <div className="mt-4 flex items-center justify-between">
                                    {reviews.links && reviews.links.map((l, idx) => (
                                        <Link
                                            key={idx}
                                            href={l.url || '#'}
                                            className={`px-3 py-1 text-xs rounded ${l.active ? 'bg-brand-gold text-brand-black' : 'bg-gray-800 text-gray-300 hover:bg-gray-700'}`}
                                            dangerouslySetInnerHTML={{ __html: l.label }}
                                        />
                                    ))}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
