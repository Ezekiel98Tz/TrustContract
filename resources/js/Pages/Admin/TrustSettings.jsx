import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import { useEffect } from 'react';

export default function TrustSettings({ auth, settings, currencies }) {
    const { data, setData, patch, processing, errors, recentlySuccessful } = useForm({
        min_for_contract: settings?.min_for_contract ?? 50,
        min_for_high_value: settings?.min_for_high_value ?? 80,
        currency_thresholds: settings?.currency_thresholds ?? {},
        require_business_verification: false,
    });

    useEffect(() => {
        const map = { ...data.currency_thresholds };
        currencies.forEach((c) => {
            if (map[c] === undefined) {
                map[c] = 0;
            }
        });
        setData('currency_thresholds', map);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const submit = (e) => {
        e.preventDefault();
        patch(route('admin.trust-settings.update'));
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="text-xl font-semibold leading-tight text-brand-gold">Trust Settings</h2>}
        >
            <Head title="Trust Settings" />
            <div className="py-12 bg-black min-h-screen">
                <div className="mx-auto max-w-5xl sm:px-6 lg:px-8">
                    <div className="bg-brand-black overflow-hidden shadow-lg sm:rounded-lg border border-gray-800">
                        <div className="p-6 text-gray-200">
                            <form onSubmit={submit} className="space-y-6">
                                <div>
                                    <label htmlFor="min_for_contract" className="block text-sm font-medium text-gray-300">Minimum profile completeness for any contract</label>
                                    <input
                                        id="min_for_contract"
                                        type="number"
                                        min="0"
                                        max="100"
                                        value={data.min_for_contract}
                                        onChange={(e) => setData('min_for_contract', Number(e.target.value))}
                                        className="mt-1 block w-48 rounded-md bg-gray-900 border-gray-700 text-white focus:border-brand-gold focus:ring-brand-gold"
                                    />
                                    {errors.min_for_contract && <p className="mt-1 text-xs text-red-300">{errors.min_for_contract}</p>}
                                </div>
                                <div>
                                    <label htmlFor="min_for_high_value" className="block text-sm font-medium text-gray-300">Minimum profile completeness for high-value</label>
                                    <input
                                        id="min_for_high_value"
                                        type="number"
                                        min="0"
                                        max="100"
                                        value={data.min_for_high_value}
                                        onChange={(e) => setData('min_for_high_value', Number(e.target.value))}
                                        className="mt-1 block w-48 rounded-md bg-gray-900 border-gray-700 text-white focus:border-brand-gold focus:ring-brand-gold"
                                    />
                                    {errors.min_for_high_value && <p className="mt-1 text-xs text-red-300">{errors.min_for_high_value}</p>}
                                </div>
                                <div>
                                    <div className="text-sm font-semibold text-gray-300">High-value thresholds by currency (cents)</div>
                                    <div className="mt-2 grid grid-cols-1 md:grid-cols-3 gap-3">
                                        {currencies.map((c) => (
                                            <div key={c}>
                                                <label className="block text-xs text-gray-400">{c}</label>
                                                <input
                                                    type="number"
                                                    min="0"
                                                    value={data.currency_thresholds?.[c] ?? 0}
                                                    onChange={(e) => setData('currency_thresholds', { ...data.currency_thresholds, [c]: Number(e.target.value) })}
                                                    className="mt-1 block w-full rounded-md bg-gray-900 border-gray-700 text-white focus:border-brand-gold focus:ring-brand-gold"
                                                />
                                                {errors[`currency_thresholds.${c}`] && <p className="mt-1 text-xs text-red-300">{errors[`currency_thresholds.${c}`]}</p>}
                                            </div>
                                        ))}
                                    </div>
                                </div>
                                <div className="mt-4">
                                    <label className="inline-flex items-center gap-2 text-sm text-gray-300">
                                        <input
                                            type="checkbox"
                                            checked={data.require_business_verification}
                                            onChange={(e) => setData('require_business_verification', e.target.checked)}
                                            className="rounded border-gray-700 bg-gray-900 text-brand-gold focus:ring-brand-gold"
                                        />
                                        Require business verification for high-value (if user has business)
                                    </label>
                                </div>
                                <div className="flex items-center gap-3">
                                    <button
                                        type="submit"
                                        disabled={processing}
                                        className="px-4 py-2 bg-brand-gold text-brand-black rounded-md font-bold hover:bg-yellow-500"
                                    >
                                        Save
                                    </button>
                                    {recentlySuccessful && <span className="text-sm text-gray-400">Saved.</span>}
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
