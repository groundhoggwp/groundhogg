export function createQueue(): WPPriorityQueue;
/**
 * Enqueued callback to invoke once idle time permits.
 */
export type WPPriorityQueueCallback = () => void;
/**
 * An object used to associate callbacks in a particular context grouping.
 */
export type WPPriorityQueueContext = {};
/**
 * Function to add callback to priority queue.
 */
export type WPPriorityQueueAdd = (element: {}, item: () => void) => void;
/**
 * Function to flush callbacks from priority queue.
 */
export type WPPriorityQueueFlush = (element: {}) => boolean;
/**
 * Reset the queue.
 */
export type WPPriorityQueueReset = () => void;
/**
 * Priority queue instance.
 */
export type WPPriorityQueue = {
    /**
     * Add callback to queue for context.
     */
    add: (element: {}, item: () => void) => void;
    /**
     * Flush queue for context.
     */
    flush: (element: {}) => boolean;
    /**
     * Reset queue.
     */
    reset: () => void;
};
//# sourceMappingURL=index.d.ts.map