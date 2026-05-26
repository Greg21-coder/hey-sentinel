import React, { useEffect, useCallback } from 'react';

interface ModalProps {
    open: boolean;
    onClose: () => void;
    title?: string;
    children: React.ReactNode;
}

const Modal: React.FC<ModalProps> = ({ open, onClose, title, children }) => {
    const handleKeyDown = useCallback(
        (e: KeyboardEvent) => {
            if (e.key === 'Escape') {
                onClose();
            }
        },
        [onClose],
    );

    useEffect(() => {
        if (open) {
            document.addEventListener('keydown', handleKeyDown);
            document.body.style.overflow = 'hidden';
        }
        return () => {
            document.removeEventListener('keydown', handleKeyDown);
            document.body.style.overflow = '';
        };
    }, [open, handleKeyDown]);

    if (!open) return null;

    return (
        <div
            className="fixed inset-0 z-50 flex items-center justify-center"
            role="dialog"
            aria-modal="true"
            aria-labelledby={title ? 'modal-title' : undefined}
        >
            {/* Backdrop */}
            <div
                className="absolute inset-0 bg-black/50"
                onClick={onClose}
                aria-hidden="true"
            />

            {/* Card */}
            <div className="relative z-10 w-full max-w-lg mx-4 bg-white rounded-xl shadow-xl overflow-hidden">
                {title && (
                    <div className="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                        <h2
                            id="modal-title"
                            className="text-lg font-semibold text-gray-900"
                        >
                            {title}
                        </h2>
                        <button
                            onClick={onClose}
                            className="text-gray-400 hover:text-gray-600 transition-colors duration-150 focus:outline-none"
                            aria-label="Close modal"
                        >
                            <svg
                                className="h-5 w-5"
                                viewBox="0 0 20 20"
                                fill="currentColor"
                                aria-hidden="true"
                            >
                                <path
                                    fillRule="evenodd"
                                    d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                    clipRule="evenodd"
                                />
                            </svg>
                        </button>
                    </div>
                )}
                <div className="px-6 py-5">{children}</div>
            </div>
        </div>
    );
};

export default Modal;
