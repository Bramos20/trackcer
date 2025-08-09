import React, { useEffect, useRef, useState } from "react";

interface MarqueeTextProps {
  text: string;
  className?: string;
}

export default function MarqueeText({ text, className = "" }: MarqueeTextProps) {
  const containerRef = useRef<HTMLDivElement>(null);
  const textRef = useRef<HTMLSpanElement>(null);
  const [shouldScroll, setShouldScroll] = useState(false);

  useEffect(() => {
    const checkOverflow = () => {
      if (containerRef.current && textRef.current) {
        const containerWidth = containerRef.current.offsetWidth;
        const textWidth = textRef.current.scrollWidth;
        setShouldScroll(textWidth > containerWidth);
      }
    };

    // Check immediately and after a small delay to ensure layout is complete
    checkOverflow();
    const timer = setTimeout(checkOverflow, 100);
    
    window.addEventListener('resize', checkOverflow);
    return () => {
      clearTimeout(timer);
      window.removeEventListener('resize', checkOverflow);
    };
  }, [text]);

  return (
    <div ref={containerRef} className={`overflow-hidden w-full ${className}`}>
      <div className={`${shouldScroll ? "animate-marquee-pause" : ""} ${shouldScroll ? "inline-flex" : ""}`}>
        <span ref={textRef} className={`${shouldScroll ? "inline-block whitespace-nowrap pr-16" : "block truncate"}`}>
          {text}
        </span>
        {shouldScroll && (
          <span className="inline-block whitespace-nowrap pr-16" aria-hidden="true">
            {text}
          </span>
        )}
      </div>
    </div>
  );
}