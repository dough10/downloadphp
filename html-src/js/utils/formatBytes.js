/**
 * format bytes to be readable by humans
 * 
 * @param {Number} bytes
 * 
 * @returns {String}
 */
export default function formatBytes(bytes) {
  if (isNaN(bytes)) return 'Error';
  const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
  if (bytes === 0) return '0 B';
  const i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)));
  return (bytes / Math.pow(1024, i)).toFixed(1) + ' ' + sizes[i];
}