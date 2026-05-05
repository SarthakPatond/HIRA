export default function LoadingState({ label = "Loading..." }) {
  return (
    <div className="container-shell py-20">
      <div className="glass-panel grid min-h-[280px] place-items-center p-8 text-center">
        <div>
          <div className="mx-auto h-14 w-14 animate-spin rounded-full border-4 border-hira-orange/20 border-t-hira-orange" />
          <p className="mt-5 text-sm font-semibold uppercase tracking-[0.22em] text-hira-orange">
            {label}
          </p>
        </div>
      </div>
    </div>
  );
}
