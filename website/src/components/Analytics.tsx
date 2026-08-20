import Script from 'next/script'

// Privacy-conscious, OPT-IN analytics. Renders NOTHING (no scripts, no cookies)
// unless NEXT_PUBLIC_GA_ID is set in the environment. When set, loads GA4 with
// IP anonymization. GA4 "enhanced measurement" auto-tracks outbound clicks to
// app.facturino.mk/signup, so signup-CTA clicks are captured as conversions
// without instrumenting each link.
export default function Analytics() {
  const id = process.env.NEXT_PUBLIC_GA_ID
  if (!id) return null

  return (
    <>
      <Script src={`https://www.googletagmanager.com/gtag/js?id=${id}`} strategy="afterInteractive" />
      <Script id="ga4-init" strategy="afterInteractive">
        {`window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag('js', new Date());
gtag('config', '${id}', { anonymize_ip: true });`}
      </Script>
    </>
  )
}
