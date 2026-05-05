export default function ErrorState({ message }) {
  return (
    <div className="container-shell py-20">
      <div className="rounded-[2rem] border border-hira-red/20 bg-white p-8 text-center shadow-soft">
        <p className="text-xs font-semibold uppercase tracking-[0.22em] text-hira-red">
          Something went wrong
        </p>
        <p className="mt-4 text-lg text-hira-ink/75">{message}</p>
      </div>
    </div>
  );
}
