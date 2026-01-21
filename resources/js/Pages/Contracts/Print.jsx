import { Head } from '@inertiajs/react';

export default function Print({ contract }) {
    const fmt = (v, c) => (v / 100).toLocaleString('en-US', { style: 'currency', currency: c || 'USD' });
    return (
        <div className="bg-white text-black">
            <Head title={`Printable Contract #${contract.id}`} />
            <div className="max-w-3xl mx-auto p-8">
                <div className="flex justify-between items-center mb-6">
                    <h1 className="text-2xl font-bold">Contract Agreement</h1>
                    <button
                        onClick={() => window.print()}
                        className="px-3 py-2 border rounded text-sm"
                    >
                        Print / Save as PDF
                    </button>
                </div>
                <div className="text-sm mb-4">
                    <div><strong>Contract ID:</strong> {contract.id}</div>
                    <div><strong>Created:</strong> {new Date(contract.created_at).toLocaleString()}</div>
                    <div><strong>Status:</strong> {contract.status}</div>
                    <div><strong>Amount:</strong> {fmt(contract.price_cents, contract.currency)}</div>
                </div>
                <hr className="my-4" />
                <div className="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <h2 className="text-lg font-semibold">Buyer</h2>
                        <div className="text-sm">
                            <div>{contract.buyer?.name}</div>
                            <div>{contract.buyer?.email}</div>
                            <div>{contract.buyer?.verification_status === 'verified' ? 'Verified' : 'Unverified'}{contract.buyer?.verification_level && contract.buyer?.verification_level !== 'none' ? ` • ${contract.buyer.verification_level}` : ''}</div>
                        </div>
                    </div>
                    <div>
                        <h2 className="text-lg font-semibold">Seller</h2>
                        <div className="text-sm">
                            <div>{contract.seller?.name}</div>
                            <div>{contract.seller?.email}</div>
                            <div>{contract.seller?.verification_status === 'verified' ? 'Verified' : 'Unverified'}{contract.seller?.verification_level && contract.seller?.verification_level !== 'none' ? ` • ${contract.seller.verification_level}` : ''}</div>
                        </div>
                    </div>
                </div>
                <hr className="my-4" />
                <div className="mb-4">
                    <h2 className="text-lg font-semibold">Terms</h2>
                    <p className="text-sm whitespace-pre-wrap">{contract.description || 'No description provided.'}</p>
                </div>
                <div className="mb-4">
                    <h2 className="text-lg font-semibold">Signatures</h2>
                    {contract.signatures?.length ? (
                        <ul className="text-sm">
                            {contract.signatures.map((s, i) => (
                                <li key={i} className="py-1">
                                    <strong>{s.user}</strong> — {new Date(s.signed_at).toLocaleString()}
                                </li>
                            ))}
                        </ul>
                    ) : (
                        <p className="text-sm">No signatures recorded yet.</p>
                    )}
                </div>
                <hr className="my-4" />
                <div className="text-xs text-gray-700">
                    Disclaimer: This printable document summarizes the contract details and party verification statuses at the time of generation. Ensure both parties are verified for increased trust. Platform provides tools but does not guarantee outcomes of agreements.
                </div>
            </div>
            <style>{`
                @media print {
                    button { display: none; }
                    body { -webkit-print-color-adjust: exact; }
                }
            `}</style>
        </div>
    );
}
