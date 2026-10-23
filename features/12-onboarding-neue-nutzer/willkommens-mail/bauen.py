import html, sys
sys.path.insert(0, '.')
from texte import T
BASE = 'https://endlech.lu'
def block(title, text, cta, url):
    return f'''
          <tr><td style="padding:0 32px 24px 32px;">
            <h2 style="margin:0 0 8px 0;font-size:18px;line-height:1.3;color:#111827;">{html.escape(title)}</h2>
            <p style="margin:0 0 12px 0;font-size:16px;line-height:1.5;color:#374151;">{html.escape(text)}</p>
            <a href="{url}" style="display:inline-block;padding:12px 20px;border-radius:9999px;background:#0e7490;color:#ffffff;font-size:16px;font-weight:bold;text-decoration:none;">{html.escape(cta)}</a>
          </td></tr>'''
for loc, t in T.items():
    e = {k: v for k, v in t.items()}
    doc = f'''<!DOCTYPE html>
<html lang="{loc}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{html.escape(t['subject'])}</title>
</head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;">
  <div style="display:none;max-height:0;overflow:hidden;">{html.escape(t['preheader'])}</div>
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;">
    <tr><td align="center" style="padding:24px 12px;">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;background:#ffffff;border-radius:12px;">
        <tr><td style="padding:28px 32px 8px 32px;font-size:22px;font-weight:bold;color:#0e7490;">Endlech.lu</td></tr>
        <tr><td style="padding:8px 32px 24px 32px;">
          <p style="margin:0 0 12px 0;font-size:16px;line-height:1.5;color:#111827;">{html.escape(t['hello'])}</p>
          <p style="margin:0;font-size:16px;line-height:1.5;color:#374151;">{html.escape(t['intro'])}</p>
        </td></tr>{block(t['s_title'], t['s_text'], t['s_cta'], f"{BASE}/{loc}/restaurants")}{block(t['v_title'], t['v_text'], t['v_cta'], f"{BASE}/{loc}/community/suggest")}{block(t['a_title'], t['a_text'], t['a_cta'], f"{BASE}/{loc}/app")}
        <tr><td style="padding:0 32px 28px 32px;font-size:16px;line-height:1.5;color:#111827;">{html.escape(t['bye'])}</td></tr>
        <tr><td style="padding:20px 32px 28px 32px;border-top:1px solid #e5e7eb;font-size:13px;line-height:1.5;color:#4b5563;">
          <p style="margin:0 0 8px 0;">{html.escape(t['why'])}</p>
          <p style="margin:0;"><a href="{{{{ unsubscribe }}}}" style="color:#0e7490;text-decoration:underline;">{html.escape(t['unsub'])}</a></p>
        </td></tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
'''
    # html.escape macht aus {{ contact.CONTACT_NAME }} nichts Falsches (keine Sonderzeichen)
    open(f'willkommen-{loc}.html', 'w', encoding='utf-8').write(doc)
    print(loc, len(doc))
