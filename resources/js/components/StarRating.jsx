export default function StarRating({ value = 0, onChange, size = 20, max = 5, readOnly = false }) {
    const stars = [];
    for (let i = 1; i <= max; i++) {
        const filled = i <= Math.round(value);
        stars.push(
            <button
                key={i}
                type="button"
                onClick={() => !readOnly && onChange && onChange(i)}
                className={`inline-flex items-center ${readOnly ? 'cursor-default' : 'cursor-pointer'} focus:outline-none`}
                aria-label={`${i} star`}
            >
                <svg
                    width={size}
                    height={size}
                    viewBox="0 0 24 24"
                    fill={filled ? '#d4af37' : 'none'}
                    stroke={filled ? '#d4af37' : '#9ca3af'}
                    strokeWidth="2"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    className="mx-0.5"
                >
                    <polygon points="12 2 15 9 22 9 17 14 19 21 12 17 5 21 7 14 2 9 9 9" />
                </svg>
            </button>
        );
    }
    return <div className="flex items-center">{stars}</div>;
}
