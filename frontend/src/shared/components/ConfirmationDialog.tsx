import React, { useState } from 'react';
import { AlertTriangle, Loader2 } from 'lucide-react';
import { Modal } from './Modal';

interface ConfirmationDialogProps {
  isOpen: boolean;
  onClose: () => void;
  onConfirm: (confirmationInput?: string) => Promise<void> | void;
  title: string;
  message: string;
  confirmLabel?: string;
  confirmText?: string;
  cancelLabel?: string;
  requiredConfirmationText?: string;
  isDestructive?: boolean;
  danger?: boolean;
}

export const ConfirmationDialog: React.FC<ConfirmationDialogProps> = ({
  isOpen,
  onClose,
  onConfirm,
  title,
  message,
  confirmLabel,
  confirmText,
  cancelLabel = 'Cancel',
  requiredConfirmationText,
  isDestructive,
  danger,
}) => {
  const effectiveConfirmLabel = confirmText || confirmLabel || 'Confirm Action';
  const effectiveDestructive = danger !== undefined ? danger : (isDestructive ?? false);
  const [inputValue, setInputValue] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const canConfirm = !requiredConfirmationText || inputValue.trim() === requiredConfirmationText;

  const handleConfirm = async () => {
    if (!canConfirm) return;
    setError(null);
    setIsSubmitting(true);
    try {
      await onConfirm(inputValue);
      setInputValue('');
      onClose();
    } catch (err: any) {
      setError(err?.response?.data?.message || err?.message || 'Operation failed');
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <Modal isOpen={isOpen} onClose={onClose} title={title} maxWidth="md">
      <div className="space-y-4">
        <div className="flex items-start space-x-3 text-slate-700">
          <div className={`p-2 rounded-xl flex-shrink-0 ${isDestructive ? 'bg-rose-50 text-rose-600' : 'bg-amber-50 text-amber-600'}`}>
            <AlertTriangle className="w-6 h-6" />
          </div>
          <p className="text-sm leading-relaxed">{message}</p>
        </div>

        {requiredConfirmationText && (
          <div className="bg-slate-50 border border-slate-200 rounded-xl p-3.5 space-y-2">
            <label className="block text-xs font-medium text-slate-600">
              To proceed, please type <span className="font-mono font-bold text-slate-900 bg-white px-1.5 py-0.5 rounded border border-slate-200">{requiredConfirmationText}</span> below:
            </label>
            <input
              type="text"
              value={inputValue}
              onChange={(e) => setInputValue(e.target.value)}
              placeholder={requiredConfirmationText}
              className="w-full text-sm border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono"
            />
          </div>
        )}

        {error && (
          <div className="text-xs text-rose-600 bg-rose-50 border border-rose-200 rounded-lg p-2.5">
            {error}
          </div>
        )}

        <div className="flex items-center justify-end space-x-3 pt-3 border-t border-slate-100">
          <button
            type="button"
            onClick={onClose}
            disabled={isSubmitting}
            className="px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 rounded-lg transition-colors"
          >
            {cancelLabel}
          </button>
          <button
            type="button"
            onClick={handleConfirm}
            disabled={!canConfirm || isSubmitting}
            className={`px-4 py-2 text-sm font-medium text-white rounded-lg transition-colors flex items-center space-x-2 disabled:opacity-50 disabled:cursor-not-allowed ${
              effectiveDestructive ? 'bg-rose-600 hover:bg-rose-700' : 'bg-blue-600 hover:bg-blue-700'
            }`}
          >
            {isSubmitting && <Loader2 className="w-4 h-4 animate-spin" />}
            <span>{effectiveConfirmLabel}</span>
          </button>
        </div>
      </div>
    </Modal>
  );
};

export default ConfirmationDialog;
