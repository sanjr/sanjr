"""Rewrites an APK so every stored (uncompressed) entry starts on a 4-byte boundary, like Android's zipalign.
Android 11+ needs resources.arsc stored and aligned. Usage: zipalign.py in.apk out.apk [--check]"""
import sys, zipfile

def align(src, dst):
    zin = zipfile.ZipFile(src)
    with zipfile.ZipFile(dst, 'w') as zout:
        for info in zin.infolist():
            data = zin.read(info.filename)
            ni = zipfile.ZipInfo(info.filename, date_time=(2026, 1, 1, 0, 0, 0))
            ni.external_attr = info.external_attr
            store = info.filename == 'resources.arsc' or info.filename.endswith(('.png', '.webp', '.jpg')) or info.compress_type == zipfile.ZIP_STORED
            ni.compress_type = zipfile.ZIP_STORED if store else zipfile.ZIP_DEFLATED
            if store:
                # local header = 30 bytes + name + extra; pad extra so the data starts at a multiple of 4
                offset = zout.fp.tell() + 30 + len(ni.filename.encode('utf-8'))
                ni.extra = b'\0' * ((4 - offset % 4) % 4)
            zout.writestr(ni, data, compress_type=ni.compress_type, compresslevel=9 if not store else None)

def check(path):
    bad = []
    with open(path, 'rb') as f:
        for info in zipfile.ZipFile(path).infolist():
            if info.compress_type != zipfile.ZIP_STORED: continue
            f.seek(info.header_offset + 26)
            n, e = int.from_bytes(f.read(2), 'little'), int.from_bytes(f.read(2), 'little')
            if (info.header_offset + 30 + n + e) % 4: bad.append(info.filename)
    return bad

if __name__ == '__main__':
    if '--check' in sys.argv:
        b = check(sys.argv[1]); print('aligned' if not b else 'NOT aligned: ' + ', '.join(b)); sys.exit(1 if b else 0)
    align(sys.argv[1], sys.argv[2])
