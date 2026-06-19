# WordPress.org plugin assets

Files in this directory are published to the WordPress.org plugin **assets**
directory (not the plugin itself) by the `update-readme-assets` workflow and
by the deploy step on release. They control how the plugin page looks.

Add the following images here (PNG or JPG):

| File                   | Size      | Purpose                          |
|------------------------|-----------|----------------------------------|
| `icon-256x256.png`     | 256×256   | Plugin icon (also used @128)     |
| `icon-128x128.png`     | 128×128   | Plugin icon (fallback)           |
| `banner-772x250.png`   | 772×250   | Header banner                    |
| `banner-1544x500.png`  | 1544×500  | Retina header banner (optional)  |
| `screenshot-1.png`     | any       | Matches "1." in readme.txt       |

A vector `icon.svg` is also accepted by WordPress.org and takes precedence
over the PNG icons — the GateCHA `favicon.svg` can be reused for this.

This README is ignored by WordPress.org and is here only to document the
expected filenames.
