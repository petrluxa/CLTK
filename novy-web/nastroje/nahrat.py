"""Nahrání webu I. ČLTK Praha na test (hosting TK Olymp, složka www/cltkv2/).

Použití (z kořene projektu):
    python nastroje/nahrat.py                       # zkušební běh – jen vypíše, co by nahrál
    python nastroje/nahrat.py --ostra               # nahraje web/ bez uploads/
    python nastroje/nahrat.py --ostra --uploads     # i obsah uploads/ (fotky, video)
    python nastroje/nahrat.py --ostra --soubory index.php inc/data.php
    python nastroje/nahrat.py --ostra --config      # nahraje deploy/cltk-config.php do kořene FTP účtu

Pojistky:
  * cíl je natvrdo /www/cltkv2/ – jinam skript nenahraje nic (kromě --config);
  * nikdy nenahraje lokální config, databáze SQLite, protokoly, klíče ani soubory *.md;
  * z data/ nahraje jen .htaccess (ostatní si web vytvoří sám);
  * existující soubor na serveru před přepsáním stáhne do deploy/zalohy/<čas>/;
  * --config smí zapsat jen /cltk-config.php a odmítne přepsat config.php webu TK Olymp.
Přístupy čte z deploy/ftp.txt (mimo git).
"""
import ftplib, io, os, sys, time, fnmatch, posixpath

KOREN = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
WEB = os.path.join(KOREN, 'web')
CIL = '/www/cltkv2'
ZAKAZANO = ['inc/config.local.php', 'data/*', '*.sqlite', '*.sqlite-journal', '*.log', '*.md',
            'cltk-config.php', '*/_qa*', '_qa*', '*.bak', '.DS_Store', 'Thumbs.db']
POVOLENO_V_DATA = {'data/.htaccess'}


def precti_pristupy():
    d = {}
    with open(os.path.join(KOREN, 'deploy', 'ftp.txt'), encoding='utf-8') as f:
        for radek in f:
            radek = radek.strip()
            if radek and not radek.startswith('#') and '=' in radek:
                k, v = radek.split('=', 1)
                d[k.strip()] = v.strip()
    return d


def smi_nahrat(rel, s_uploads):
    rel = rel.replace('\\', '/')
    if rel in POVOLENO_V_DATA:
        return True
    if rel.startswith('uploads/') and not s_uploads and rel != 'uploads/.htaccess':
        return False
    return not any(fnmatch.fnmatch(rel, vz) for vz in ZAKAZANO)


def soubory_webu(s_uploads, jen=None):
    if jen:
        for rel in jen:
            rel = rel.replace('\\', '/').lstrip('/')
            if not os.path.isfile(os.path.join(WEB, rel)):
                sys.exit(f'Soubor neexistuje: web/{rel}')
            if not smi_nahrat(rel, True):
                sys.exit(f'Tenhle soubor se na server nenahrává: {rel}')
            yield rel
        return
    for kde, _, jmena in os.walk(WEB):
        for j in jmena:
            rel = os.path.relpath(os.path.join(kde, j), WEB).replace('\\', '/')
            if smi_nahrat(rel, s_uploads):
                yield rel


class Ftp:
    def __init__(self, p):
        self.f = ftplib.FTP(p['host'], timeout=60)
        self.f.login(p['user'], p['pass'])
        self.f.encoding = 'utf-8'
        self.slozky = set()

    def existuje(self, cesta):
        try:
            return self.f.size(cesta) is not None
        except ftplib.all_errors:
            return False

    def mkdirs(self, slozka):
        casti = [c for c in slozka.split('/') if c]
        cesta = ''
        for c in casti:
            cesta += '/' + c
            if cesta in self.slozky:
                continue
            try:
                self.f.mkd(cesta)
            except ftplib.error_perm:
                pass
            self.slozky.add(cesta)

    def stahni(self, cesta):
        b = io.BytesIO()
        self.f.retrbinary('RETR ' + cesta, b.write)
        return b.getvalue()

    def nahraj(self, lokalni, cesta):
        with open(lokalni, 'rb') as fh:
            self.f.storbinary('STOR ' + cesta, fh)


def main():
    a = sys.argv[1:]
    ostra = '--ostra' in a
    s_uploads = '--uploads' in a
    jen = a[a.index('--soubory') + 1:] if '--soubory' in a else None
    if jen:
        jen = [x for x in jen if not x.startswith('--')]
    p = precti_pristupy()
    zaloha = os.path.join(KOREN, 'deploy', 'zalohy', time.strftime('%Y%m%d-%H%M%S'))

    if '--config' in a:
        cil, zdroj = '/cltk-config.php', os.path.join(KOREN, 'deploy', 'cltk-config.php')
        assert posixpath.basename(cil) == 'cltk-config.php' and cil != '/config.php'
        print(('NAHRÁVÁM' if ostra else 'zkušebně') + f'  deploy/cltk-config.php → {cil}')
        if ostra:
            ftp = Ftp(p)
            if ftp.existuje(cil):
                os.makedirs(zaloha, exist_ok=True)
                open(os.path.join(zaloha, 'cltk-config.php'), 'wb').write(ftp.stahni(cil))
            ftp.nahraj(zdroj, cil)
            ftp.f.quit()
        return

    seznam = sorted(soubory_webu(s_uploads, jen))
    velikost = sum(os.path.getsize(os.path.join(WEB, r)) for r in seznam)
    print(f'{len(seznam)} souborů, {velikost / 1e6:.1f} MB → {CIL}/' + ('' if ostra else '   (ZKUŠEBNÍ BĚH, nic se nenahrálo)'))
    if not ostra:
        for r in seznam[:40]:
            print('  ', r)
        if len(seznam) > 40:
            print(f'   … a dalších {len(seznam) - 40}')
        return

    ftp = Ftp(p)
    zalohovano = 0
    for i, rel in enumerate(seznam, 1):
        cesta = f'{CIL}/{rel}'
        assert cesta.startswith(CIL + '/') and '..' not in rel
        ftp.mkdirs(posixpath.dirname(cesta))
        if ftp.existuje(cesta):
            cil_z = os.path.join(zaloha, *rel.split('/'))
            os.makedirs(os.path.dirname(cil_z), exist_ok=True)
            open(cil_z, 'wb').write(ftp.stahni(cesta))
            zalohovano += 1
        ftp.nahraj(os.path.join(WEB, rel), cesta)
        if i % 50 == 0 or i == len(seznam):
            print(f'  {i}/{len(seznam)}')
    # složky, které si web potřebuje vytvořit sám
    ftp.mkdirs(CIL + '/data/relace')
    ftp.f.quit()
    print(f'Hotovo. Záloha přepsaných souborů ({zalohovano}): {zaloha if zalohovano else "nic se nepřepisovalo"}')


if __name__ == '__main__':
    main()
