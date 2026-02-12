import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';

export default function Show({ auth, dispute }) {
    const { patch, post, processing } = useForm();
    const [body, setBody] = useState('');
    const [mediatorId, setMediatorId] = useState(dispute.mediator?.id || auth.user.id);
    const [notes, setNotes] = useState(dispute.mediation_notes || '');

    const assignMediator = () => {
        patch(route('admin.disputes.review', { dispute: dispute.id }), {
            data: { status: 'mediate', mediator_id: mediatorId, mediation_notes: notes || 'Mediator assigned' },
        });
    };
    const postMessage = (e) => {
        e.preventDefault();
        if (!body.trim()) return;
        post(route('admin.disputes.messages.store', { dispute: dispute.id }), {
            data: { body },
            onSuccess: () => setBody(''),
        });
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="text-xl font-semibold leading-tight text-brand-gold">Dispute #{dispute.id}</h2>}
        >
            <Head title={`Dispute #${dispute.id}`} />
            <div className="py-12 bg-black min-h-screen">
                <div className="mx-auto max-w-5xl sm:px-6 lg:px-8">
                    <div className="bg-brand-black overflow-hidden shadow-lg sm:rounded-lg border border-gray-800">
                        <div className="p-6 text-gray-200">
                            <div className="flex justify-between items-start">
                                <div>
                                    <div className="text-sm text-gray-400">Contract</div>
                                    <Link href={route('contracts.show', dispute.contract?.id)} className="text-brand-gold hover:text-white">
                                        #{dispute.contract?.id} {dispute.contract?.title || ''}
                                    </Link>
                                    <div className="mt-2 text-sm text-gray-400">Initiator: {dispute.initiator?.name}</div>
                                    <div className="mt-1 text-sm text-gray-400">Status: {dispute.status} {dispute.resolution ? `• ${dispute.resolution}` : ''}</div>
                                </div>
                                <div>
                                    <Link href={route('admin.disputes.index')} className="text-gray-400 hover:text-white">Back to Disputes</Link>
                                </div>
                            </div>

                            <div className="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div className="bg-gray-900 p-4 rounded border border-gray-700">
                                    <div className="text-sm font-bold text-gray-300 uppercase">Assign Mediator</div>
                                    <div className="mt-2">
                                        <input
                                            type="number"
                                            value={mediatorId}
                                            onChange={(e) => setMediatorId(parseInt(e.target.value || '0', 10))}
                                            className="w-full rounded-md bg-gray-800 border-gray-700 text-white"
                                            placeholder="Mediator user ID (Admin)"
                                        />
                                    </div>
                                    <div className="mt-2">
                                        <textarea
                                            value={notes}
                                            onChange={(e) => setNotes(e.target.value)}
                                            className="w-full rounded-md bg-gray-800 border-gray-700 text-white"
                                            rows={3}
                                            placeholder="Mediation notes"
                                        />
                                    </div>
                                    <div className="mt-3">
                                        <button
                                            onClick={assignMediator}
                                            disabled={processing}
                                            className="inline-flex items-center px-4 py-2 bg-blue-800 border border-blue-700 rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700"
                                        >
                                            Assign Mediator
                                        </button>
                                    </div>
                                </div>

                                <div className="bg-gray-900 p-4 rounded border border-gray-700">
                                    <div className="text-sm font-bold text-gray-300 uppercase">Post Message</div>
                                    <form onSubmit={postMessage} className="mt-2 space-y-2">
                                        <textarea
                                            value={body}
                                            onChange={(e) => setBody(e.target.value)}
                                            className="w-full rounded-md bg-gray-800 border-gray-700 text-white"
                                            rows={4}
                                            placeholder="Message to parties"
                                        />
                                        <button
                                            type="submit"
                                            disabled={processing}
                                            className="inline-flex items-center px-4 py-2 bg-brand-gold text-brand-black rounded-md font-bold hover:bg-yellow-500"
                                        >
                                            Send
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <div className="mt-8">
                                <div className="text-sm font-bold text-gray-300 uppercase">Timeline</div>
                                {dispute.logs?.length ? (
                                    <ul className="mt-2 space-y-3">
                                        {dispute.logs.map((log) => (
                                            <li key={log.id} className="bg-gray-800 p-3 rounded border border-gray-700 text-sm text-gray-300">
                                                <div className="flex justify-between">
                                                    <div>
                                                        <span className="font-semibold text-white">{log.action}</span>
                                                        {(log.from_status || log.to_status) && (
                                                            <span className="ml-2 text-gray-400">Status: {log.from_status || '—'} → {log.to_status || '—'}</span>
                                                        )}
                                                        {log.notes && <div className="mt-1 text-gray-400">{log.notes}</div>}
                                                    </div>
                                                    <div className="text-xs text-gray-500">{new Date(log.created_at).toLocaleString()}</div>
                                                </div>
                                            </li>
                                        ))}
                                    </ul>
                                ) : (
                                    <div className="mt-2 text-xs text-gray-500">No timeline entries.</div>
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
