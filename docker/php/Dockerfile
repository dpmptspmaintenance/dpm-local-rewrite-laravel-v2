FROM php:8.3-fpm

# System deps
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libicu-dev \
    && docker-php-ext-install pdo_mysql mysqli zip gd mbstring exif pcntl bcmath intl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# LibreOffice (writer component only, not the full suite) for high-fidelity
# .docx -> .pdf conversion via `soffice --headless --convert-to pdf` — used
# by SuratTugasGeneratorService so the downloaded PDF matches Word's own
# "Save As PDF" output, instead of PHPWord's dompdf-based HTML/CSS renderer
# (which has a limited CSS2.1-ish subset and drifted noticeably from Word's
# actual layout on this app's Surat Tugas template — tables, spacing, line
# breaks all rendered differently). fontconfig + the DejaVu/Liberation fonts
# are pulled in automatically as dependencies of libreoffice-writer.
RUN apt-get update && apt-get install -y \
    libreoffice-writer \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www
