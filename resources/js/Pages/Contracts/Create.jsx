import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, Link } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import StarRating from '@/components/StarRating';
import InputError from '@/components/InputError';
import InputLabel from '@/components/InputLabel';
import TextInput from '@/components/TextInput';
import { composeTemplate } from '@/utils/contractTemplates';

export default function Create({ auth, currencies = ['USD','EUR','TZS'], currency_thresholds = {}, min_for_high_value = 80, dispute_rate_warn_percent = 5, min_for_contract = 50, profile_completion_percent = null }) {
    const { data, setData, post, processing, errors } = useForm({
        title: '',
        description: '',
        price_cents: '',
        currency: 'USD',
        deadline_at: '',
        counterparty_id: '',
    });
    const [step, setStep] = useState(1);
    const [query, setQuery] = useState('');
    const [roleFilter, setRoleFilter] = useState('');
    const [results, setResults] = useState([]);
    const [loading, setLoading] = useState(false);
    const debounceRef = useRef(null);
    const [insightsOpen, setInsightsOpen] = useState(false);
    const [insights, setInsights] = useState(null);

    const [clientError, setClientError] = useState('');
    const isHighValue = (() => {
        const threshold = currency_thresholds[(data.currency || 'USD')] ?? 50000;
        return (Number(data.price_cents || 0)) >= threshold;
    })();
    const requiredPercent = isHighValue ? min_for_high_value : min_for_contract;
    const completionPercent = typeof profile_completion_percent === 'number' ? profile_completion_percent : null;
    const hasVerificationForHighValue = ['standard','advanced'].includes((auth?.user?.verification_level || 'none'));
    const canSubmit = (() => {
        if (isHighValue) {
            return hasVerificationForHighValue && (completionPercent === null || completionPercent >= min_for_high_value);
        }
        return completionPercent === null || completionPercent >= min_for_contract;
    })();
    const submit = (e) => {
        e.preventDefault();
        setClientError('');
        if (!canSubmit) {
            const msg = isHighValue
                ? `High-value requires standard verification and ≥${min_for_high_value}% profile completeness.`
                : `Reach ≥${min_for_contract}% profile completeness to create contracts.`;
            setClientError(msg);
            return;
        }
        post(route('contracts.store'));
    };

    useEffect(() => {
        if (debounceRef.current) clearTimeout(debounceRef.current);
        debounceRef.current = setTimeout(async () => {
            setLoading(true);
            try {
                const url = new URL(route('counterparties.search'));
                if (query) url.searchParams.set('q', query);
                if (roleFilter) url.searchParams.set('role', roleFilter);
                const res = await fetch(url);
                const json = await res.json();
                setResults(json.results || []);
            } catch (e) {
                setResults([]);
            } finally {
                setLoading(false);
            }
        }, 300);
        return () => debounceRef.current && clearTimeout(debounceRef.current);
    }, [query, roleFilter]);

    const next = () => setStep((s) => Math.min(5, s + 1));
    const back = () => setStep((s) => Math.max(1, s - 1));
    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="text-xl font-semibold leading-tight text-brand-gold">Create New Contract</h2>}
        >
            <Head title="Create Contract" />

            <div className="py-12 bg-black min-h-screen">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="bg-brand-black overflow-hidden shadow-lg sm:rounded-lg border border-gray-800">
                        <div className="p-6 text-gray-200">
                            <form onSubmit={submit} className="max-w-2xl">
                                {(errors.email || errors.profile || errors.verification) && (
                                    <div className="mb-6 rounded-md border border-yellow-700 bg-yellow-900/20 p-4">
                                        <div className="text-sm text-yellow-200 font-semibold">Action Required</div>
                                        <div className="mt-1 text-sm text-gray-200">
                                            {errors.email && <div>{errors.email}</div>}
                                            {errors.profile && <div>{errors.profile}</div>}
                                            {errors.verification && <div>{errors.verification}</div>}
                                        </div>
                                        <div className="mt-2">
                                            <Link href={route('account.personal-information.edit')} className="inline-flex items-center px-3 py-1 rounded-md bg-brand-gold text-brand-black text-xs font-bold hover:bg-yellow-500">
                                                Go to Personal Information
                                            </Link>
                                        </div>
                                    </div>
                                )}
                                <div className="flex items-center justify-between mb-6">
                                    <div className="flex items-center gap-2 text-xs">
                                        <span className={`px-2 py-1 rounded ${step >= 1 ? 'bg-brand-gold text-brand-black' : 'bg-gray-800 text-gray-400'}`}>1. Counterparty</span>
                                        <span className={`px-2 py-1 rounded ${step >= 2 ? 'bg-brand-gold text-brand-black' : 'bg-gray-800 text-gray-400'}`}>2. Terms</span>
                                        <span className={`px-2 py-1 rounded ${step >= 3 ? 'bg-brand-gold text-brand-black' : 'bg-gray-800 text-gray-400'}`}>3. Amount</span>
                                        <span className={`px-2 py-1 rounded ${step >= 4 ? 'bg-brand-gold text-brand-black' : 'bg-gray-800 text-gray-400'}`}>4. Timeline</span>
                                        <span className={`px-2 py-1 rounded ${step >= 5 ? 'bg-brand-gold text-brand-black' : 'bg-gray-800 text-gray-400'}`}>5. Review</span>
                                    </div>
                                </div>
                                {step === 1 && (
                                    <div className="space-y-4">
                                        <div className="grid grid-cols-1 md:grid-cols-3 gap-3">
                                            <div className="md:col-span-2">
                                                <InputLabel htmlFor="search" value="Search Counterparty" className="text-gray-300" />
                                                <TextInput
                                                    id="search"
                                                    value={query}
                                                    className="mt-1 block w-full bg-gray-900 text-white border-gray-700 focus:border-brand-gold focus:ring-brand-gold"
                                                    onChange={(e) => setQuery(e.target.value)}
                                                    placeholder="Name or email"
                                                />
                                            </div>
                                            <div>
                                                <InputLabel htmlFor="roleFilter" value="Role" className="text-gray-300" />
                                                <select
                                                    id="roleFilter"
                                                    value={roleFilter}
                                                    className="mt-1 block w-full border-gray-700 bg-gray-900 text-white rounded-md"
                                                    onChange={(e) => setRoleFilter(e.target.value)}
                                                >
                                                    <option value="">Any</option>
                                                    <option value="Buyer">Buyer</option>
                                                    <option value="Seller">Seller</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div className="mt-3">
                                            {loading ? (
                                                <div className="text-sm text-gray-400">Searching…</div>
                                            ) : results.length === 0 ? (
                                                <div className="text-sm text-gray-500">No results yet. Type to search.</div>
                                            ) : (
                                                <ul className="space-y-3">
                                                    {results.map((u) => (
                                                        <li key={u.id} className={`p-3 rounded-md border ${data.counterparty_id == u.id ? 'border-brand-gold' : 'border-gray-800'} bg-gray-900`}>
                                                            <div className="flex items-center justify-between">
                                                                <div>
                                                                    <div className="text-sm font-bold text-white">{u.name}</div>
                                                                    <div className="text-xs text-gray-400">{u.email} • {u.role}</div>
                                                                    <div className="mt-1 flex items-center gap-2">
                                                                        <span title="Advanced: enhanced checks • Standard: ID and address verified • Basic: email, phone, country • Unverified"
                                                                            className={`inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold border ${
                                                                            u.verification_status === 'verified'
                                                                                ? 'bg-green-900 text-green-200 border-green-800'
                                                                                : 'bg-red-900 text-red-200 border-red-800'
                                                                        }`}>
                                                                            {u.verification_status === 'verified' ? 'Verified' : 'Unverified'}{u.verification_level && u.verification_level !== 'none' ? ` • ${u.verification_level}` : ''}
                                                                        </span>
                                                                        <span className="inline-flex items-center">
                                                                            <StarRating value={u.rating_avg || 0} readOnly size={16} />
                                                                            <span className="ml-1 text-xs text-gray-400">({u.rating_count})</span>
                                                                        </span>
                                                                    </div>
                                                                </div>
                                                                <div className="flex items-center gap-2">
                                                                    <button
                                                                        type="button"
                                                                        onClick={async () => {
                                                                            try {
                                                                                const res = await fetch(route('counterparties.insights', u.id));
                                                                                const json = await res.json();
                                                                                setInsights(json);
                                                                                setInsightsOpen(true);
                                                                            } catch {}
                                                                        }}
                                                                        className="inline-flex items-center px-3 py-2 bg-gray-800 rounded-md text-gray-200 text-xs font-semibold border border-gray-700 hover:bg-gray-700"
                                                                    >
                                                                        View History
                                                                    </button>
                                                                    <button
                                                                    type="button"
                                                                    onClick={() => { setData('counterparty_id', u.id); setStep(2); }}
                                                                    className="inline-flex items-center px-3 py-2 bg-brand-gold rounded-md text-brand-black text-xs font-bold"
                                                                >
                                                                    Select
                                                                    </button>
                                                                </div>
                                                            </div>
                                                            {u.verification_status !== 'verified' && (
                                                                <div className="mt-2 text-xs text-yellow-300">Unverified user. Proceed with caution.</div>
                                                            )}
                                                        </li>
                                                    ))}
                                                </ul>
                                            )}
                                        </div>
                                        <InputError message={errors.counterparty_id} className="mt-2" />
                                    </div>
                                )}
                                {step === 2 && (
                                <div>
                                    <InputLabel htmlFor="title" value="Contract Title" className="text-gray-300" />
                                    <TextInput
                                        id="title"
                                        name="title"
                                        value={data.title}
                                        className="mt-1 block w-full bg-gray-900 text-white border-gray-700 focus:border-brand-gold focus:ring-brand-gold"
                                        autoComplete="title"
                                        isFocused={true}
                                        onChange={(e) => setData('title', e.target.value)}
                                        required
                                    />
                                    <InputError message={errors.title} className="mt-2" />
                                </div>
                                )}
                                {step === 2 && (
                                <div className="mt-4">
                                    <InputLabel htmlFor="template" value="Quick Template" className="text-gray-300" />
                                    <select
                                        id="template"
                                        className="mt-1 block w-full border-gray-700 bg-gray-900 text-white rounded-md"
                                        onChange={(e) => {
                                            const key = e.target.value;
                                            if (!key) return;
                                            const ctx = {
                                                title: data.title,
                                                buyerName: auth.user.role === 'Buyer' ? auth.user.name : 'Buyer',
                                                sellerName: auth.user.role === 'Seller' ? auth.user.name : 'Seller',
                                                amount: (Number(data.price_cents || 0) / 100).toLocaleString('en-US', { style: 'currency', currency: data.currency || 'USD' }),
                                                currency: data.currency || 'USD',
                                                deadline: data.deadline_at || '',
                                            };
                                            const text = composeTemplate(key, ctx);
                                            setData('description', text);
                                        }}
                                    >
                                        <option value="">Select a template…</option>
                                        <option value="goods_sale">Goods Sale</option>
                                        <option value="service_agreement">Service Agreement</option>
                                        <option value="freelance_project">Freelance Project</option>
                                        <option value="formal_goods_sale">Formal Goods Sale</option>
                                        <option value="formal_service_agreement">Formal Service Agreement</option>
                                        <option value="formal_freelance_project">Formal Freelance Project</option>
                                    </select>
                                </div>
                                )}
                                {step === 2 && (
                                <div className="mt-4">
                                    <InputLabel htmlFor="description" value="Description / Terms" className="text-gray-300" />
                                    <textarea
                                        id="description"
                                        name="description"
                                        value={data.description}
                                        className="mt-1 block w-full border-gray-700 bg-gray-900 text-white focus:border-brand-gold focus:ring-brand-gold rounded-md shadow-sm"
                                        rows="4"
                                        onChange={(e) => setData('description', e.target.value)}
                                    />
                                    <InputError message={errors.description} className="mt-2" />
                                </div>
                                )}
                                {step === 3 && (
                                <div className="mt-4">
                                    <InputLabel htmlFor="price_cents" value="Price (in Cents)" className="text-gray-300" />
                                    <TextInput
                                        id="price_cents"
                                        name="price_cents"
                                        type="number"
                                        value={data.price_cents}
                                        className="mt-1 block w-full bg-gray-900 text-white border-gray-700 focus:border-brand-gold focus:ring-brand-gold"
                                        onChange={(e) => setData('price_cents', e.target.value)}
                                        required
                                    />
                                    <div className="grid grid-cols-1 md:grid-cols-3 gap-3 mt-3">
                                        <div>
                                            <InputLabel htmlFor="currency" value="Currency" className="text-gray-300" />
                                            <select
                                                id="currency"
                                                name="currency"
                                                value={data.currency}
                                                className="mt-1 block w-full border-gray-700 bg-gray-900 text-white rounded-md"
                                                onChange={(e) => setData('currency', e.target.value)}
                                            >
                                                {currencies.map((c) => (
                                                    <option key={c} value={c}>{c}</option>
                                                ))}
                                            </select>
                                        </div>
                                        <div className="md:col-span-2 flex items-end">
                                            <div>
                                                <p className="text-sm text-gray-400">
                                                    Value: {(data.price_cents / 100).toLocaleString('en-US', { style: 'currency', currency: data.currency || 'USD' })}
                                                </p>
                                                <p className="text-xs text-gray-500">
                                                    ≈ {(() => {
                                                        const rates = { USD: 1.0, EUR: 1.09, TZS: 0.00038 };
                                                        const rate = rates[(data.currency || 'USD')];
                                                        const usd = (Number(data.price_cents || 0) * rate) / 100;
                                                        return usd.toLocaleString('en-US', { style: 'currency', currency: 'USD' });
                                                    })()}
                                                </p>
                                                <p className="text-xs text-gray-400 mt-1">
                                                    High-value threshold for {data.currency || 'USD'}: {(currency_thresholds[(data.currency || 'USD')] ?? 50000) / 100} {data.currency || 'USD'} • Requires Standard verification and ≥{min_for_high_value}% profile completeness
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    <InputError message={errors.price_cents} className="mt-2" />
                                </div>
                                )}
                                {step === 4 && (
                                <div className="mt-4">
                                    <InputLabel htmlFor="deadline_at" value="Deadline (Optional)" className="text-gray-300" />
                                    <TextInput
                                        id="deadline_at"
                                        name="deadline_at"
                                        type="date"
                                        value={data.deadline_at}
                                        className="mt-1 block w-full bg-gray-900 text-white border-gray-700 focus:border-brand-gold focus:ring-brand-gold"
                                        onChange={(e) => setData('deadline_at', e.target.value)}
                                    />
                                    <InputError message={errors.deadline_at} className="mt-2" />
                                </div>
                                )}
                                {step === 5 && (
                                    <div className="mt-4 space-y-4">
                                        <div className="rounded-md border border-gray-800 bg-gray-900 p-4">
                                            <div className="text-sm text-gray-300">Counterparty ID: {data.counterparty_id || '—'}</div>
                                            <div className="text-sm text-gray-300">Title: {data.title || '—'}</div>
                                            <div className="text-sm text-gray-300">Price: {(data.price_cents / 100).toLocaleString('en-US', { style: 'currency', currency: data.currency || 'USD' })}</div>
                                            {data.deadline_at && <div className="text-sm text-gray-300">Deadline: {data.deadline_at}</div>}
                                        </div>
                                        {clientError && (
                                            <div className="rounded-md border border-yellow-700 bg-yellow-900/20 p-4 text-sm text-yellow-200">
                                                <div className="font-semibold">Action Required</div>
                                                <div className="mt-1">{clientError}</div>
                                                <div className="mt-2 flex items-center gap-2">
                                                    {typeof completionPercent === 'number' && (
                                                        <span className="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold border bg-gray-800 text-gray-300 border-gray-700">
                                                            Current profile completeness: {completionPercent}%
                                                        </span>
                                                    )}
                                                    <Link href={route('account.personal-information.edit')} className="inline-flex items-center px-3 py-1 rounded-md bg-brand-gold text-brand-black text-xs font-bold hover:bg-yellow-500">
                                                        Go to Personal Information
                                                    </Link>
                                                </div>
                                            </div>
                                        )}
                                        {!clientError && !canSubmit && (
                                            <div className="rounded-md border border-yellow-700 bg-yellow-900/20 p-4 text-sm text-yellow-200">
                                                <div className="font-semibold">Action Required</div>
                                                <div className="mt-1">
                                                    {isHighValue ? (
                                                        <span>High-value requires standard verification and ≥{min_for_high_value}% profile completeness.</span>
                                                    ) : (
                                                        <span>Reach ≥{min_for_contract}% profile completeness to create contracts.</span>
                                                    )}
                                                </div>
                                                <div className="mt-2 flex items-center gap-2">
                                                    {typeof completionPercent === 'number' && (
                                                        <span className="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold border bg-gray-800 text-gray-300 border-gray-700">
                                                            Current profile completeness: {completionPercent}%
                                                        </span>
                                                    )}
                                                    <Link href={route('account.personal-information.edit')} className="inline-flex items-center px-3 py-1 rounded-md bg-brand-gold text-brand-black text-xs font-bold hover:bg-yellow-500">
                                                        Go to Personal Information
                                                    </Link>
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                )}
                                <div className="flex items-center justify-between mt-6">
                                    {step > 1 ? (
                                        <button
                                            type="button"
                                            onClick={back}
                                            className="inline-flex items-center px-4 py-2 rounded-md border border-gray-700 bg-gray-900 text-gray-200 font-semibold text-sm hover:bg-gray-800 disabled:opacity-50"
                                        >
                                            Back
                                        </button>
                                    ) : <div />}
                                    {step < 5 ? (
                                        <button
                                            type="button"
                                            onClick={next}
                                            className="inline-flex items-center px-4 py-2 bg-brand-gold border border-transparent rounded-md font-bold text-xs text-brand-black uppercase tracking-widest hover:bg-yellow-500"
                                        >
                                            Next
                                        </button>
                                    ) : (
                                        <button
                                            type="submit"
                                            className={`ms-4 inline-flex items-center px-4 py-2 border border-transparent rounded-md font-bold text-xs uppercase tracking-widest transition ease-in-out duration-150 ${
                                                canSubmit
                                                    ? 'bg-brand-gold text-brand-black hover:bg-yellow-500 focus:bg-yellow-500 active:bg-yellow-600'
                                                    : 'bg-gray-800 text-gray-400 cursor-not-allowed'
                                            }`}
                                            disabled={processing || !canSubmit}
                                        >
                                            Create Contract
                                        </button>
                                    )}
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            {insightsOpen && insights && (
                <div className="fixed inset-0 z-50 flex items-center justify-center px-4">
                    <div
                        className="absolute inset-0 bg-black/70"
                        onClick={() => setInsightsOpen(false)}
                        aria-hidden="true"
                    />
                    <div className="relative w-full max-w-2xl rounded-xl bg-brand-black border border-gray-800 shadow-2xl">
                        <div className="p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <h3 className="text-xl font-bold text-brand-gold">{insights.user.name}</h3>
                                    <div className="text-xs text-gray-400">{insights.user.email} • {insights.user.role}</div>
                                    <div className="mt-1 flex items-center gap-2">
                                        <span title="Advanced: enhanced checks • Standard: ID and address verified • Basic: email, phone, country • Unverified"
                                            className={`inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold border ${
                                            insights.user.verification_status === 'verified'
                                                ? 'bg-green-900 text-green-200 border-green-800'
                                                : 'bg-red-900 text-red-200 border-red-800'
                                        }`}>
                                            {insights.user.verification_status === 'verified' ? 'Verified' : 'Unverified'}{insights.user.verification_level && insights.user.verification_level !== 'none' ? ` • ${insights.user.verification_level}` : ''}
                                        </span>
                                        <span className="inline-flex items-center">
                                            <StarRating value={insights.user.rating_avg || 0} readOnly size={16} />
                                            <span className="ml-1 text-xs text-gray-400">({insights.user.rating_count})</span>
                                        </span>
                                        <span
                                            className={`inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold border ${
                                                (insights.user.dispute_rate || 0) >= (dispute_rate_warn_percent || 5)
                                                    ? 'bg-red-900 text-red-200 border-red-800'
                                                    : 'bg-yellow-900 text-yellow-200 border-yellow-800'
                                            }`}
                                            title="Dispute rate is based on disputes over paid/failed transactions"
                                        >
                                            Disputes {typeof insights.user.dispute_rate === 'number' ? `${insights.user.dispute_rate}%` : '—'}
                                            {typeof insights.user.dispute_count === 'number' ? ` • ${insights.user.dispute_count}` : ''}
                                        </span>
                                    </div>
                                </div>
                                <button
                                    onClick={() => setInsightsOpen(false)}
                                    className="rounded-md p-2 text-gray-400 hover:text-brand-gold hover:bg-gray-800"
                                >
                                    Close
                                </button>
                            </div>
                            <div className="mt-2">
                                <Link
                                    href={route('counterparties.reviews', insights.user.id)}
                                    className="inline-flex items-center px-3 py-2 bg-brand-gold rounded-md text-brand-black text-xs font-bold hover:bg-yellow-500"
                                >
                                    View Full Reviews
                                </Link>
                            </div>
                            <div className="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <h4 className="text-sm font-bold text-gray-300 uppercase tracking-wide">Recent Reviews</h4>
                                    {insights.recent_reviews.length === 0 ? (
                                        <p className="text-xs text-gray-500 mt-2">No reviews yet.</p>
                                    ) : (
                                        <ul className="mt-2 space-y-2">
                                            {insights.recent_reviews.map((rv) => (
                                                <li key={rv.id} className="bg-gray-900 p-3 rounded border border-gray-700">
                                                    <div className="flex items-center justify-between">
                                                        <div className="text-xs text-gray-400">
                                                            {rv.reviewer?.name || 'User'}
                                                        </div>
                                                        <StarRating value={rv.rating} readOnly size={16} />
                                                    </div>
                                                    {rv.comment && <p className="mt-1 text-xs text-gray-200">{rv.comment}</p>}
                                                    <div className="mt-1 text-[10px] text-gray-500">{new Date(rv.created_at).toLocaleString()}</div>
                                                </li>
                                            ))}
                                        </ul>
                                    )}
                                </div>
                                <div>
                                    <h4 className="text-sm font-bold text-gray-300 uppercase tracking-wide">Recent Contracts</h4>
                                    {insights.recent_contracts.length === 0 ? (
                                        <p className="text-xs text-gray-500 mt-2">No recent contracts.</p>
                                    ) : (
                                        <ul className="mt-2 space-y-2">
                                            {insights.recent_contracts.map((c) => (
                                                <li key={c.id} className="bg-gray-900 p-3 rounded border border-gray-700">
                                                    <div className="text-xs text-gray-200 font-semibold">{c.title}</div>
                                                    <div className="text-[11px] text-gray-400">{(c.price_cents / 100).toLocaleString('en-US', { style: 'currency', currency: c.currency })} • {c.status}</div>
                                                    <div className="mt-1 text-[10px] text-gray-500">{new Date(c.created_at).toLocaleDateString()}</div>
                                                </li>
                                            ))}
                                        </ul>
                                    )}
                                </div>
                            </div>
                            <div className="mt-4">
                                <h4 className="text-sm font-bold text-gray-300 uppercase tracking-wide">Risk Insights</h4>
                                <div className="mt-2 grid grid-cols-3 gap-3">
                                    <div className="bg-gray-900 p-3 rounded border border-gray-700">
                                        <div className="text-[11px] text-gray-400">Paid</div>
                                        <div className="text-sm font-bold text-gray-200">{insights.user.paid_count ?? '—'}</div>
                                    </div>
                                    <div className="bg-gray-900 p-3 rounded border border-gray-700">
                                        <div className="text-[11px] text-gray-400">Failed</div>
                                        <div className="text-sm font-bold text-gray-200">{insights.user.failed_count ?? '—'}</div>
                                    </div>
                                    <div className="bg-gray-900 p-3 rounded border border-gray-700">
                                        <div className="text-[11px] text-gray-400">Disputes</div>
                                        <div className="text-sm font-bold text-gray-200">{insights.user.dispute_count ?? '—'}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </AuthenticatedLayout>
    );
}
