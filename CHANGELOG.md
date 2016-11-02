v1.006
==========
- Fixed bug for glyph 婦 in Regular weight
- Added subsetted TTF version without Latin characters (fallback to system Latin fonts when embedded in mobile app).
- Note that the subsetted fonts are renamed to "CYanHei TCHK".

v1.005
==========
- Fixed upstream bug 袱 (U+88B1).
- Fixed glyph 簪 (U+7C2A).
- Tuned HKSCS glyphs with "既" components.
- Tuned glyphs with "之" components.
- Added subsetted TTF version (for mobile app embedding).

v1.004
==========
- Removed some entries from the PALT table to prevent proportional width behavior. Some glyphs (e.g. ，。？！) are expected to be in full width.  
- More HK codepoint coverage.
- 86 more characters added to webfont build.

v1.003
======
- Glyph of proportional (half-width) digit “1” modified, with bottom horizontal line removed.
- Glyph of proportional (half-width) letter “g” modified to single-storey form. 
- Fixed 迓 (U+8FD3) incorrectly mapped to JP glyph. Instead, it should be modified.

v1.002
======
- Fixed 搵's glyph (U+6435) not mapped to 揾.
- Fixed 麿 (U+9EBF) incorrectly mapped to CN.
- Added coverage of IICORE characters with Hong Kong source identifier.  
- Additional codepoints mapped for HK.  

v1.001
======
- Fixed 虜 (U+865C) of Regular weight to HK glyph.
- Fixed 匾 (U+533E) of light weight to HK glyph.
- Other minor glyph adjustments.

v1.000
======
- Initial release.