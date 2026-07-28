import zipfile
from pathlib import Path

output = Path('Acceptance_Letter.odt')
content_xml = '''<?xml version="1.0" encoding="UTF-8"?>
<office:document-content xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0" xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0" xmlns:table="urn:oasis:names:tc:opendocument:xmlns:table:1.0" xmlns:draw="urn:oasis:names:tc:opendocument:xmlns:drawing:1.0" xmlns:fo="urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0" office:version="1.2">
  <office:automatic-styles>
    <style:style style:name="Title" style:family="paragraph" style:parent-style-name="Heading_20_1">
      <style:text-properties fo:font-size="18pt" fo:font-weight="bold"/>
    </style:style>
    <style:style style:name="Bold" style:family="paragraph">
      <style:text-properties fo:font-weight="bold"/>
    </style:style>
    <style:style style:name="Centered" style:family="paragraph">
      <style:paragraph-properties fo:text-align="center"/>
    </style:style>
    <style:style style:name="Right" style:family="paragraph">
      <style:paragraph-properties fo:text-align="right"/>
    </style:style>
  </office:automatic-styles>
  <office:body>
    <office:text>
      <text:p text:style-name="Centered"><text:span text:style-name="Bold">Radha Krishna Enterprises</text:span></text:p>
      <text:p text:style-name="Centered">Apposite Rajmata Temple Ashapur Road, Raichur, Karnataka 584101</text:p>
      <text:p text:style-name="Centered">Phone: 9380666173</text:p>
      <text:p text:style-name="Centered"><text:span text:style-name="Bold">ACCEPTANCE LETTER</text:span></text:p>
      <text:p/>
      <text:p><text:span text:style-name="Bold">TO,</text:span></text:p>
      <text:p>JSS SMI UG AND PG STUDIES</text:p>
      <text:p>Vidyagiri, Dharwad, 580004</text:p>
      <text:p/>
      <text:p><text:span text:style-name="Bold">Subject:</text:span> Acceptance of proposed project entitled as “Unisex Salon Management System”.</text:p>
      <text:p/>
      <text:p>Respected sir,</text:p>
      <text:p>This is to certify that Mr. Vageesh Hiremath (U02BF23S0206), a student of JSS SMI College, has successfully completed the project work entitled “Unisex Salon Management System” for our organization.</text:p>
      <text:p>The student has fulfilled all the requirements and expectations of the client and has completed the assigned work within the specified time period. The project was carried out sincerely, efficiently, and according to the given specifications and business needs.</text:p>
      <text:p>We appreciate the student’s dedication, hard work, and professional approach during the development of the project. The work submitted has been found satisfactory.</text:p>
      <text:p/>
      <text:p text:style-name="Right">Your Sincerely,</text:p>
      <text:p text:style-name="Right">Radha Krishna Enterprises</text:p>
    </office:text>
  </office:body>
</office:document-content>
'''

styles_xml = '''<?xml version="1.0" encoding="UTF-8"?>
<office:document-styles xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0" xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0" office:version="1.2">
  <office:styles/>
  <office:automatic-styles/>
  <office:master-styles/>
</office:document-styles>
'''

meta_xml = '''<?xml version="1.0" encoding="UTF-8"?>
<office:document-meta xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" xmlns:meta="urn:oasis:names:tc:opendocument:xmlns:meta:1.0" office:version="1.2">
  <office:meta>
    <meta:generator>Python generated</meta:generator>
  </office:meta>
</office:document-meta>
'''

manifest_xml = '''<?xml version="1.0" encoding="UTF-8"?>
<manifest:manifest xmlns:manifest="urn:oasis:names:tc:opendocument:xmlns:manifest:1.0">
  <manifest:file-entry manifest:full-path="/" manifest:media-type="application/vnd.oasis.opendocument.text"/>
  <manifest:file-entry manifest:full-path="content.xml" manifest:media-type="text/xml"/>
  <manifest:file-entry manifest:full-path="styles.xml" manifest:media-type="text/xml"/>
  <manifest:file-entry manifest:full-path="meta.xml" manifest:media-type="text/xml"/>
</manifest:manifest>
'''

with zipfile.ZipFile(output, 'w', compression=zipfile.ZIP_DEFLATED) as zf:
    zf.writestr('mimetype', 'application/vnd.oasis.opendocument.text', compress_type=zipfile.ZIP_STORED)
    zf.writestr('content.xml', content_xml)
    zf.writestr('styles.xml', styles_xml)
    zf.writestr('meta.xml', meta_xml)
    zf.writestr('META-INF/manifest.xml', manifest_xml)

print(f'Created {output.resolve()}')
