/**
 * Digital Newspaper Reader Utilities
 * Math, scaling, bounds checking, and touch gesture calculations.
 */

export const MIN_SCALE = 0.5;
export const MAX_SCALE = 3.5;
export const SCALE_STEP = 0.25;

export interface Point {
  x: number;
  y: number;
}

export interface Dimensions {
  width: number;
  height: number;
}

export interface TouchCoordinate {
  clientX: number;
  clientY: number;
}

/**
 * Calculates optimal scale to fit page inside container by width
 */
export function calculateFitToWidth(
  container: Dimensions,
  page: Dimensions,
  padding: number = 32
): number {
  if (!container.width || !page.width) return 1;
  const availableWidth = Math.max(container.width - padding, 200);
  const scale = availableWidth / page.width;
  return clampScale(scale);
}

/**
 * Calculates optimal scale to fit page inside container by height
 */
export function calculateFitToHeight(
  container: Dimensions,
  page: Dimensions,
  padding: number = 32
): number {
  if (!container.height || !page.height) return 1;
  const availableHeight = Math.max(container.height - padding, 200);
  const scale = availableHeight / page.height;
  return clampScale(scale);
}

/**
 * Calculates scale to fit entire page in view (both width and height)
 */
export function calculateFitToScreen(
  container: Dimensions,
  page: Dimensions,
  padding: number = 32
): number {
  const scaleW = calculateFitToWidth(container, page, padding);
  const scaleH = calculateFitToHeight(container, page, padding);
  return Math.min(scaleW, scaleH);
}

/**
 * Clamps scale value between MIN_SCALE and MAX_SCALE
 */
export function clampScale(scale: number): number {
  return Math.max(MIN_SCALE, Math.min(MAX_SCALE, Math.round(scale * 100) / 100));
}

/**
 * Calculates clamped pan offset so page doesn't disappear out of view
 */
export function clampPanOffset(
  pan: Point,
  container: Dimensions,
  page: Dimensions,
  scale: number
): Point {
  const scaledWidth = page.width * scale;
  const scaledHeight = page.height * scale;

  let maxPanX = 0;
  let minPanX = 0;
  if (scaledWidth > container.width) {
    const overflowX = (scaledWidth - container.width) / 2;
    maxPanX = overflowX;
    minPanX = -overflowX;
  }

  let maxPanY = 0;
  let minPanY = 0;
  if (scaledHeight > container.height) {
    const overflowY = (scaledHeight - container.height) / 2;
    maxPanY = overflowY;
    minPanY = -overflowY;
  }

  return {
    x: Math.max(minPanX, Math.min(maxPanX, pan.x)),
    y: Math.max(minPanY, Math.min(maxPanY, pan.y)),
  };
}

/**
 * Calculates euclidean distance between two touch points
 */
export function getTouchDistance(touch1: TouchCoordinate, touch2: TouchCoordinate): number {
  const dx = touch1.clientX - touch2.clientX;
  const dy = touch1.clientY - touch2.clientY;
  return Math.sqrt(dx * dx + dy * dy);
}

/**
 * Calculates midpoint between two touch points
 */
export function getTouchCenter(touch1: TouchCoordinate, touch2: TouchCoordinate): Point {
  return {
    x: (touch1.clientX + touch2.clientX) / 2,
    y: (touch1.clientY + touch2.clientY) / 2,
  };
}
