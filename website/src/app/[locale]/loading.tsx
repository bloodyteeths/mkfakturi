import Image from 'next/image'

export default function Loading() {
  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-gradient-to-br from-white via-indigo-50/30 to-cyan-50/30">
      <div className="flex flex-col items-center gap-5">
        {/* Logo with orbital rings */}
        <div className="relative w-24 h-24">
          {/* Outer glow pulse */}
          <div
            className="absolute inset-[-16px] rounded-full opacity-30"
            style={{
              background: 'radial-gradient(circle, rgba(79,70,229,0.4) 0%, transparent 70%)',
              animation: 'loaderPulse 2s ease-in-out infinite',
            }}
          />

          {/* Outer orbital ring — slow */}
          <div
            className="absolute inset-[-12px] rounded-full"
            style={{
              border: '2px solid transparent',
              borderTopColor: '#4f46e5',
              borderRightColor: '#06b6d4',
              animation: 'loaderSpin 2.5s cubic-bezier(0.4, 0, 0.2, 1) infinite',
            }}
          />

          {/* Inner orbital ring — fast, opposite */}
          <div
            className="absolute inset-[-4px] rounded-full"
            style={{
              border: '2px solid transparent',
              borderBottomColor: '#4f46e5',
              borderLeftColor: '#818cf8',
              animation: 'loaderSpin 1.5s cubic-bezier(0.4, 0, 0.2, 1) infinite reverse',
            }}
          />

          {/* Logo container with subtle breathing */}
          <div
            className="relative w-24 h-24 flex items-center justify-center"
            style={{ animation: 'loaderBreathe 2s ease-in-out infinite' }}
          >
            <Image
              src="/brand/facturino_icon.png"
              alt="Facturino"
              width={80}
              height={80}
              className="drop-shadow-lg"
              priority
            />
          </div>
        </div>

        {/* Brand name with shimmer */}
        <div className="relative overflow-hidden">
          <span
            className="text-lg font-bold tracking-wide"
            style={{ color: '#4f46e5' }}
          >
            Facturino
          </span>
          <div
            className="absolute inset-0"
            style={{
              background: 'linear-gradient(90deg, transparent 0%, rgba(255,255,255,0.8) 50%, transparent 100%)',
              backgroundSize: '200% 100%',
              animation: 'loaderShimmer 2s linear infinite',
            }}
          />
        </div>

        {/* Three dot loader */}
        <div className="flex gap-1.5">
          {[0, 1, 2].map((i) => (
            <div
              key={i}
              className="w-2 h-2 rounded-full"
              style={{
                backgroundColor: '#4f46e5',
                animation: `loaderDot 1.2s ease-in-out ${i * 0.2}s infinite`,
              }}
            />
          ))}
        </div>
      </div>

      <style dangerouslySetInnerHTML={{ __html: `
        @keyframes loaderSpin {
          from { transform: rotate(0deg); }
          to { transform: rotate(360deg); }
        }
        @keyframes loaderPulse {
          0%, 100% { transform: scale(0.9); opacity: 0.2; }
          50% { transform: scale(1.3); opacity: 0.4; }
        }
        @keyframes loaderBreathe {
          0%, 100% { transform: scale(1); }
          50% { transform: scale(1.05); }
        }
        @keyframes loaderShimmer {
          from { background-position: 200% 0; }
          to { background-position: -200% 0; }
        }
        @keyframes loaderDot {
          0%, 80%, 100% { transform: scale(0.6); opacity: 0.3; }
          40% { transform: scale(1); opacity: 1; }
        }
      `}} />
    </div>
  )
}
