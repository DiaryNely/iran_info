export function Toast({ toast, onClose }) {
  if (!toast) return null;

  return (
    <div className={`toast toast-${toast.type}`}>
      <span>{toast.message}</span>
      <button type="button" onClick={onClose}>
        x
      </button>
    </div>
  );
}
