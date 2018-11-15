<?php namespace Zephyrus\Network;

interface ContentType
{
    const HTML = 'text/html'; /* RFC 2854 */
    const PLAIN = 'text/plain'; /* RFC 2046 */
    const XML = 'text/xml'; /* RFC 3023 */
    const XML_APP = 'application/xml'; /* RFC 7303 */
    const VCARD = 'text/vcard'; /* RFC 6350 */
    const CSS = 'text/css'; /* RFC 2318 */
    const CSV = 'text/csv'; /* RFC 4180 */
    const JSON = 'application/json'; /* RFC 4627 */
    const JAVASCRIPT = 'application/javascript'; /* RFC 4329 */
    const PDF = 'application/pdf'; /* RFC 3778 */
    const GZIP = 'application/gzip'; /* RFC 6713 */
    const SSE = 'text/event-stream';
    const APPLICATION = 'application/octet-stream';
}
