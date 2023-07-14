'use client'

import useSWR from 'swr'
import Card from "@/components/Card/Card";

export default function Page() {
    const { data } = useSWR('http://localhost:8080/api/v1/hello', (api: string) => fetch(api).then(res => res.json()));
    return <div className={"text-sm font-mono box-content p-4"}>
        {data ? (
            data.map((title: string) => <Card key={title} title={title}></Card>)
        ) : "Loading"}
    </div>
}
