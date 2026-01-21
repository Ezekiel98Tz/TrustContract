export default function Checkbox({ className = '', ...props }) {
    return (
        <input
            {...props}
            type="checkbox"
            className={
                'rounded border-gray-700 bg-gray-900 text-brand-gold shadow-sm focus:ring-brand-gold ' +
                className
            }
        />
    );
}
