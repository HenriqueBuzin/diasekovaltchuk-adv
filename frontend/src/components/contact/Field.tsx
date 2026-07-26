import type { ChangeEvent, HTMLAttributes } from 'react';

import type { ContactFieldName, FieldLimit } from '../../types';

interface FieldProps {
  as?: 'input' | 'textarea';
  id: string;
  label: string;
  name: ContactFieldName;
  value: string;
  error: string | undefined;
  limits: FieldLimit;
  onBlur: (event: ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => void;
  onChange: (event: ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => void;
  type?: string;
  placeholder: string;
  autoComplete?: string;
  inputMode?: HTMLAttributes<HTMLInputElement>['inputMode'];
}

export function Field({
  as = 'input',
  id,
  label,
  name,
  value,
  error,
  limits,
  onBlur,
  onChange,
  type,
  placeholder,
  autoComplete,
  inputMode
}: FieldProps) {
  const commonProps = {
    className: `form-control${error ? ' is-invalid' : ''}`,
    id,
    name,
    value,
    minLength: limits.min,
    maxLength: limits.max,
    'aria-describedby': `${id}-error`,
    'aria-invalid': Boolean(error),
    onBlur,
    onChange,
    placeholder,
    required: true
  };

  return (
    <div className="form-floating">
      {as === 'textarea' ? (
        <textarea {...commonProps} />
      ) : (
        <input {...commonProps} type={type} autoComplete={autoComplete} inputMode={inputMode} />
      )}
      <label htmlFor={id}>{label}</label>
      <div className="field-error" id={`${id}-error`} aria-live="polite">
        {error}
      </div>
    </div>
  );
}
