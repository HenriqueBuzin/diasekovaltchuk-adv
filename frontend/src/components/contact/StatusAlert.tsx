import type { FormStatus } from './useContactForm';

interface StatusAlertProps {
  status: FormStatus | null;
  onClose: () => void;
}

export function StatusAlert({ status, onClose }: StatusAlertProps) {
  if (!status) return null;

  return (
    <div className={`alert alert-${status.type} alert-dismissible fade show`} role="alert">
      {status.message}
      <button type="button" className="btn-close" aria-label="Fechar" onClick={onClose} />
    </div>
  );
}
