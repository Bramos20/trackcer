import { AppContent } from '@/components/app-content'
import { AppShell } from '@/components/app-shell'
import { AppSidebar } from '@/components/app-sidebar'
import { SidebarTrigger } from '@/components/ui/sidebar'
import { Separator } from '@/components/ui/separator'
import { 
  Breadcrumb,
  BreadcrumbList,
  BreadcrumbItem,
  BreadcrumbLink,
  BreadcrumbSeparator,
  BreadcrumbPage,
} from '@/components/ui/breadcrumb'
import { Link } from '@inertiajs/react'
import { ThemeToggle } from '@/components/theme-toggle'
import { type PropsWithChildren } from 'react'
import * as React from 'react'

export interface BreadcrumbItemType {
  title: string
  href?: string
}

interface AppSidebarLayoutProps {
  breadcrumbs?: BreadcrumbItemType[]
  user: {
    name: string
    email: string
    avatar?: string
  }
}

export default function AppSidebarLayout({ children, breadcrumbs = [], user }: PropsWithChildren<AppSidebarLayoutProps>) {
  return (
    <AppShell variant="sidebar">
      <AppSidebar user={user} />
      <AppContent variant="sidebar">
        <header className="flex h-16 shrink-0 items-center justify-between gap-2 px-4 border-b">
          <div className="flex items-center gap-2">
            <SidebarTrigger className="-ml-1" />
            <Separator orientation="vertical" className="mr-2 h-4" />
            <Breadcrumb>
              <BreadcrumbList>
                {breadcrumbs.map((item, index) => {
                  const isLast = index === breadcrumbs.length - 1
                  
                  return (
                    <React.Fragment key={index}>
                      {index > 0 && <BreadcrumbSeparator />}
                      <BreadcrumbItem>
                        {isLast || !item.href ? (
                          <BreadcrumbPage>{item.title}</BreadcrumbPage>
                        ) : (
                          <BreadcrumbLink asChild>
                            <Link href={item.href}>{item.title}</Link>
                          </BreadcrumbLink>
                        )}
                      </BreadcrumbItem>
                    </React.Fragment>
                  )
                })}
              </BreadcrumbList>
            </Breadcrumb>
          </div>
          <ThemeToggle />
        </header>
        <div className="flex h-full flex-1 flex-col gap-4 p-4 min-w-0 overflow-x-hidden">
          {children}
        </div>
      </AppContent>
    </AppShell>
  )
}