<?php namespace Zephyrus\Network;

interface ContentType
{
    // Default classic content types for form submission (multipart being for upload purposes).
    const FORM = 'application/x-www-form-urlencoded';
    const FORM_MULTIPART = 'multipart/form-data';

    const ANY = '*/*';
    const HTML = 'text/html'; /* RFC 2854 */
    const PLAIN = 'text/plain'; /* RFC 2046 */
    const XML = 'text/xml'; /* RFC 3023 */
    const XML_APP = 'application/xml'; /* RFC 7303 */
    const VCARD = 'text/vcard'; /* RFC 6350 */
    const CSS = 'text/css'; /* RFC 2318 */
    const CSV = 'text/csv'; /* RFC 4180 */
    const JSON = 'application/json'; /* RFC 4627 */
    const JSON_API = 'application/vnd.api+json'; /* RFC 7159 */
    const JAVASCRIPT = 'text/javascript'; /* RFC 4329 */
    const PDF = 'application/pdf'; /* RFC 3778 */
    const GZIP = 'application/gzip'; /* RFC 6713 */
    const SSE = 'text/event-stream';
    const APPLICATION = 'application/octet-stream';
    const BZIP = 'application/x-bzip';
    const BZIP2 = 'application/x-bzip2';
    const C_SHELL = 'application/x-csh';
    const MS_WORD = 'application/msword';
    const MS_EXCEL = 'application/vnd.ms-excel';
    const MS_VISIO = 'application/vnd.visio';
    const MS_POWERPOINT = 'application/vnd.ms-powerpoint';
    const CALENDAR = 'text/calendar';
    const JAR = 'application/java-archive';
    const RTF = 'application/rtf';
    const JAVASCRIPT_APP = 'application/javascript';
    const AUDIO_MPEG = 'audio/mpeg';
    const AUDIO_WAV = 'audio/wav';
    const AUDIO_WEBM = 'audio/webm';
    const AUDIO_OGG = 'audio/ogg';
    const VIDEO_OGG = 'video/ogg';
    const VIDEO_MPEG = 'video/mpeg';
    const VIDEO_WEBM = 'video/webm';
    const OGG_APP = 'application/ogg';
    const RAR = 'application/vnd.rar';
    const TAR = 'application/x-tar';
    const ZIP = 'application/zip';
    const ZIP_7 = 'application/x-7z-compressed';
    const APPLE_INSTALLER = 'application/vnd.apple.installer+xml';
    const FLASH = 'application/x-shockwave-flash';
    const IMAGE_SVG = 'image/svg+xml';
    const IMAGE_BMP = 'image/bmp';
    const IMAGE_GIF = 'image/gif';
    const IMAGE_JPEG = 'image/jpeg';
    const IMAGE_PNG = 'image/png';
    const IMAGE_TIFF = 'image/tiff';
    const IMAGE_WEBP = 'image/webp';
    const IMAGE_ICON = 'image/vnd.microsoft.icon';
}
