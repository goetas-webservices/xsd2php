<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
xmlns:xsd="http://www.w3.org/2001/XMLSchema"
>


<xsl:template match="/">
	<all>
		<xsl:apply-templates select="/all/class|//class/prop/class" />
	</all>
</xsl:template>


<xsl:template match="prop[not(@type) and class]">
	<xsl:copy>
		<xsl:attribute name="type-ns" >
			<xsl:value-of select="class/@ns"/>
		</xsl:attribute>
		<xsl:attribute name="type-name" >
			<xsl:value-of select="class/@sub-ns"/>
			<xsl:text>#</xsl:text>
			<xsl:value-of select="class/@name"/>
		</xsl:attribute>
		<xsl:apply-templates  select="@*"/>
		<xsl:apply-templates />
	</xsl:copy>
</xsl:template>

<xsl:template match="class[@sub-ns and string-length(@sub-ns)=0]">
	<xsl:apply-templates/>
</xsl:template>

<xsl:template match="class[@sub-ns and string-length(@sub-ns)>0]">
	<xsl:copy>
		<xsl:attribute name="ns" >
			<xsl:value-of select="@ns"/>
		</xsl:attribute>
		<xsl:attribute name="name" >
			<xsl:value-of select="substring(@sub-ns, 0, string-length(@sub-ns))"/>
			<xsl:text>#</xsl:text>
			<xsl:value-of select="@name"/>
		</xsl:attribute>
		<xsl:apply-templates />
	</xsl:copy>
</xsl:template>




<xsl:template match="node()|@*" priority="-4">
	<xsl:copy>
		<xsl:apply-templates  select="@*"/>
		<xsl:apply-templates />
	</xsl:copy>
</xsl:template>

</xsl:stylesheet>
