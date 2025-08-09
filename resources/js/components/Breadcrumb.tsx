import {
    Breadcrumb,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbSeparator,
  } from "@components/ui/breadcrumb"
  
  import { Home } from "lucide-react"
  
  export default function AppBreadcrumb({ segments = [] }) {
    return (
      <Breadcrumb className="mb-6">
        {/* Home link */}
        <BreadcrumbItem>
          <BreadcrumbLink href="/" className="flex items-center gap-1">
            <Home className="w-4 h-4" />
            Home
          </BreadcrumbLink>
        </BreadcrumbItem>
  
        {segments.map(({ label, href }, index) => (
          <BreadcrumbItem key={index}>
            <BreadcrumbSeparator />
            <BreadcrumbLink
              href={href}
              aria-current={index === segments.length - 1 ? "page" : undefined}
            >
              {label}
            </BreadcrumbLink>
          </BreadcrumbItem>
        ))}
      </Breadcrumb>
    )
  }  