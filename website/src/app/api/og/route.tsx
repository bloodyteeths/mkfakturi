import { ImageResponse } from 'next/og'
import { type NextRequest } from 'next/server'

export const runtime = 'edge'

const LOCALE_LABELS: Record<string, string> = {
  mk: 'Македонски',
  sq: 'Shqip',
  tr: 'Türkçe',
  en: 'English',
}

export async function GET(req: NextRequest) {
  const { searchParams } = req.nextUrl
  const title = searchParams.get('title') || 'Facturino'
  const locale = searchParams.get('locale') || 'mk'
  const type = searchParams.get('type') || 'article'

  const langLabel = LOCALE_LABELS[locale] || ''

  const isArticle = type === 'article'

  return new ImageResponse(
    (
      <div
        style={{
          height: '100%',
          width: '100%',
          display: 'flex',
          flexDirection: 'column',
          justifyContent: 'space-between',
          background: 'linear-gradient(135deg, #4338ca 0%, #3b82f6 50%, #06b6d4 100%)',
          padding: '60px',
          fontFamily: 'system-ui, sans-serif',
        }}
      >
        <div style={{ display: 'flex', flexDirection: 'column', gap: '20px' }}>
          {isArticle && (
            <div
              style={{
                display: 'flex',
                alignItems: 'center',
                gap: '12px',
              }}
            >
              <div
                style={{
                  background: 'rgba(255,255,255,0.2)',
                  borderRadius: '20px',
                  padding: '6px 16px',
                  color: 'white',
                  fontSize: '20px',
                  fontWeight: 600,
                }}
              >
                Blog
              </div>
              {langLabel && (
                <div
                  style={{
                    background: 'rgba(255,255,255,0.1)',
                    borderRadius: '20px',
                    padding: '6px 16px',
                    color: 'rgba(255,255,255,0.8)',
                    fontSize: '18px',
                  }}
                >
                  {langLabel}
                </div>
              )}
            </div>
          )}
          <div
            style={{
              fontSize: title.length > 60 ? '42px' : '52px',
              fontWeight: 800,
              color: 'white',
              lineHeight: 1.2,
              maxWidth: '900px',
              overflow: 'hidden',
              textOverflow: 'ellipsis',
            }}
          >
            {title}
          </div>
        </div>
        <div
          style={{
            display: 'flex',
            justifyContent: 'space-between',
            alignItems: 'flex-end',
          }}
        >
          <div
            style={{
              display: 'flex',
              alignItems: 'center',
              gap: '16px',
            }}
          >
            <div
              style={{
                width: '48px',
                height: '48px',
                background: 'white',
                borderRadius: '12px',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                fontSize: '28px',
                fontWeight: 900,
                color: '#4338ca',
              }}
            >
              F
            </div>
            <div
              style={{
                fontSize: '32px',
                fontWeight: 700,
                color: 'white',
              }}
            >
              Facturino
            </div>
          </div>
          <div
            style={{
              fontSize: '18px',
              color: 'rgba(255,255,255,0.6)',
            }}
          >
            facturino.mk
          </div>
        </div>
      </div>
    ),
    {
      width: 1200,
      height: 630,
    }
  )
}
