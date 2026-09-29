const fs = require('fs');
const path = require('path');

const outPath = path.join(__dirname, 'user-role-header-update-guide.pdf');

const sections = [
  'HomePage Header Dynamic Role Update Guide',
  '',
  'What this change does',
  '- Moves the profile circle to the top-right header area.',
  '- Shows the selected login role next to the circle.',
  '- Keeps the text format as Admin, Manager, Mechanic.',
  '- Uses User as the fallback if no role is saved.',
  '',
  'File 1: index.html',
  'Replace the current login submit script at the bottom of the file.',
  'Save the selected role into localStorage after a valid login.',
  'Keep the redirect to HomePage.html after success.',
  '',
  'File 2: HomePage.html',
  'Replace the header block so the circle appears before the role text.',
  'Add a DOMContentLoaded script before </body> to read localStorage.',
  'If no role exists, show User by default.',
  '',
  'File 3: mainStyle.css',
  'Change .header to display:flex and align the items to the right.',
  'Remove absolute positioning from .profile-circle.',
  'Reset #userRoleDisplay margin so it sits neatly beside the circle.',
  '',
  'Result',
  'The top-right area will show the circle and the logged-in role side by side.',
  'The display will be dynamic and match the login selection.'
];

function wrapText(text, maxChars) {
  const lines = [];
  const parts = text.split(' ');
  let current = '';

  for (const part of parts) {
    const next = current ? `${current} ${part}` : part;
    if (next.length > maxChars) {
      if (current) lines.push(current);
      current = part;
    } else {
      current = next;
    }
  }

  if (current) lines.push(current);
  return lines.length ? lines : [''];
}

const wrapped = [];
for (const line of sections) {
  if (!line) {
    wrapped.push('');
    continue;
  }
  const indent = line.startsWith('- ') ? '  ' : '';
  const raw = indent ? line.slice(2) : line;
  const lines = wrapText(raw, 88);
  for (let i = 0; i < lines.length; i++) {
    wrapped.push((indent && i === 0 ? '- ' : indent && i > 0 ? '  ' : '') + lines[i]);
  }
}

const pages = [];
const linesPerPage = 42;
for (let i = 0; i < wrapped.length; i += linesPerPage) {
  pages.push(wrapped.slice(i, i + linesPerPage));
}

function pdfEscape(text) {
  return text
    .replace(/\\/g, '\\\\')
    .replace(/\(/g, '\\(')
    .replace(/\)/g, '\\)');
}

function buildPageStream(lines, isFirstPage) {
  const titleFont = 16;
  const bodyFont = 12;
  const startY = 790;
  const lineHeight = 16;
  const chunks = [];

  chunks.push('BT');
  chunks.push(`/F1 ${isFirstPage ? titleFont : bodyFont} Tf`);
  let y = startY;

  if (isFirstPage) {
    chunks.push(`50 ${y} Td`);
    chunks.push(`(${pdfEscape(lines[0] || '')}) Tj`);
    y -= 28;
    chunks.push('/F1 12 Tf');
    for (let i = 1; i < lines.length; i++) {
      const text = lines[i];
      if (!text) {
        y -= 10;
        continue;
      }
      chunks.push(`50 ${y} Td`);
      chunks.push(`(${pdfEscape(text)}) Tj`);
      y -= lineHeight;
    }
  } else {
    for (const text of lines) {
      if (!text) {
        y -= 10;
        continue;
      }
      chunks.push(`50 ${y} Td`);
      chunks.push(`(${pdfEscape(text)}) Tj`);
      y -= lineHeight;
    }
  }

  chunks.push('ET');
  return chunks.join('\n');
}

const pageCount = pages.length;
const catalogObj = 1;
const pagesObj = 2;
const fontObj = 3;
const firstPageObj = 4;
const firstContentObj = 5;

const objects = [];
objects[catalogObj] = `<< /Type /Catalog /Pages ${pagesObj} 0 R >>`;
objects[pagesObj] = `<< /Type /Pages /Kids [${Array.from({ length: pageCount }, (_, i) => `${firstPageObj + i * 2} 0 R`).join(' ')}] /Count ${pageCount} >>`;
objects[fontObj] = `<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>`;

for (let i = 0; i < pageCount; i++) {
  const pageObj = firstPageObj + i * 2;
  const contentObj = firstContentObj + i * 2;
  const stream = buildPageStream(pages[i], i === 0);
  const length = Buffer.byteLength(stream, 'utf8');
  objects[pageObj] = `<< /Type /Page /Parent ${pagesObj} 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 ${fontObj} 0 R >> >> /Contents ${contentObj} 0 R >>`;
  objects[contentObj] = `<< /Length ${length} >>\nstream\n${stream}\nendstream`;
}

let pdf = '%PDF-1.4\n';
const offsets = [0];

for (let i = 1; i <= objects.length; i++) {
  if (!objects[i]) continue;
  offsets[i] = Buffer.byteLength(pdf, 'utf8');
  pdf += `${i} 0 obj\n${objects[i]}\nendobj\n`;
}

const xrefPos = Buffer.byteLength(pdf, 'utf8');
pdf += `xref\n0 ${objects.length}\n`;
pdf += '0000000000 65535 f \n';
for (let i = 1; i < objects.length; i++) {
  const off = String(offsets[i] || 0).padStart(10, '0');
  pdf += `${off} 00000 n \n`;
}
pdf += `trailer\n<< /Size ${objects.length} /Root ${catalogObj} 0 R >>\nstartxref\n${xrefPos}\n%%EOF\n`;

fs.writeFileSync(outPath, pdf, 'binary');
console.log(outPath);
