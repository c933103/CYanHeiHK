v1.010
==========
- Fixed inconsistent 勻 component in 均. 
- Workset 9: 141 remapped glyphs.
- Workset 10: 108 remapped and 8 modified glyphs.
- Modified subset inclusion logic. Previously, all modified (incl. remapped) characters will be included as the original scope of work is to only modify characters that are not rarely used. But now most of the characters in the supported charset (Big5+HKSCS) have been reviewed, it is no longer appropriate to include all modified characters by default.

v1.009
==========
- **Removed special mappings of 兌 and 𥁕 components.** This is a difficult decision, but unfortunately the special mappings have caused more trouble than I had expected in practice.
- New workset 8: reviewed and modified more codepoints.
- Fixed some inconsistency glyphs.

v1.008a
==========
- Fixed "妊" not remapped to the "⿰女𡈼" form

v1.008
==========
- Remapped/Redesigned characters to use the "hooked" form of the 七, 乜, 巽(巳) components:
  * 七 related: 七吒柒𠮟皂𨳍
  * 乜 related: 乜也乸他拖吔哋地她弛施池牠祂釶馳
  * 巽(巳) related: 僎巽撰潠簨蟤譔選鐉饌
- Remapped characters so that the first stroke of 壬 component is a slash (丿):
  * 壬 related: 任凭壬姙恁栠賃飪鵀
- Updated mappings of 腼 and 膦 to use the font's preferred form (月 instead of ⺼)
- Use HK standard form for 蘸 and 馥

v1.007
==========
- Added workset 6 to remap 27 more characters.
- Fixed mapping of 聰 and 荊.
- Reviewed and Removed ~370 characters from the subsetted version, and added some more characters deemed useful to it.

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