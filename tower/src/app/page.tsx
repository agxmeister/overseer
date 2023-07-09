'use client'

import useSWR from 'swr'
import Card from "@/app/card";

export default function Page() {
    const { data } = useSWR('http://localhost:8080/api/v1/hello', (api: string) => fetch(api).then(res => res.json()));

    return <Card title={data}></Card>
}
